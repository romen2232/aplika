# Chapter 03: VPC & Networking

> Create a VPC with public subnets, an internet gateway, route tables, and security groups. This is the network foundation for all AWS resources.

**Estimated duration:** 30-60 minutes | **Cost:** ~$0 (VPC itself is free)

---

## What is a VPC?

A **VPC (Virtual Private Cloud)** is your own isolated network within AWS. Think of it as your private data center in the cloud. You control the IP address range, subnets, routing, and firewall rules.

> **Exam note:** VPC is tested extensively. Know the difference between public and private subnets, route tables, internet gateways, NAT gateways, and security groups vs NACLs.

## Architecture

```
VPC 10.0.0.0/16 (aplika-vpc)
├── Public Subnet A (10.0.1.0/24) — us-east-1a
│   ├── ALB
│   ├── ECS Tasks (api, worker, frontend)
│   └── RDS (in dedicated subnet group)
├── Public Subnet B (10.0.2.0/24) — us-east-1b
│   ├── ALB (second AZ)
│   └── ECS Tasks (if scaled)
├── Internet Gateway
└── Security Groups
    ├── aplika-alb-sg (ports 80, 443 from internet)
    ├── aplika-task-sg (ports from ALB SG only)
    └── aplika-rds-sg (port 5432 from task SG only)
```

### Why Public Subnets Only?

We're using **public subnets** (subnets with a route to an internet gateway) for all resources, including ECS tasks and RDS. This saves ~$32/month by not needing a NAT gateway.

**Tradeoff:** Resources in public subnets have public IPs. We protect them with security groups (firewall rules). In production, you'd use private subnets + NAT — see the theory section at the end.

---

## CloudFormation Template

```yaml
# vpc.yaml
AWSTemplateFormatVersion: '2010-09-09'
Description: Aplika VPC with public subnets and security groups

Parameters:
  EnvironmentName:
    Type: String
    Default: aplika
    Description: Prefix for all resources

Resources:
  # ============================================================
  # VPC — the virtual network boundary
  # ============================================================
  VPC:
    Type: AWS::EC2::VPC
    Properties:
      CidrBlock: 10.0.0.0/16          # 65,536 IP addresses
      EnableDnsSupport: true           # Required for Route 53 resolution
      EnableDnsHostnames: true         # Instances get DNS names
      Tags:
        - Key: Name
          Value: !Sub '${EnvironmentName}-vpc'

  # ============================================================
  # Internet Gateway — connects VPC to the public internet
  # ============================================================
  InternetGateway:
    Type: AWS::EC2::InternetGateway
    Properties:
      Tags:
        - Key: Name
          Value: !Sub '${EnvironmentName}-igw'

  # Attach the IGW to the VPC
  InternetGatewayAttachment:
    Type: AWS::EC2::VPCGatewayAttachment
    Properties:
      InternetGatewayId: !Ref InternetGateway
      VpcId: !Ref VPC

  # ============================================================
  # Public Subnets — two AZs for high availability
  # ============================================================
  PublicSubnetA:
    Type: AWS::EC2::Subnet
    Properties:
      VpcId: !Ref VPC
      AvailabilityZone: us-east-1a
      CidrBlock: 10.0.1.0/24          # 256 IP addresses
      MapPublicIpOnLaunch: true        # Instances get public IPs
      Tags:
        - Key: Name
          Value: !Sub '${EnvironmentName}-public-a'

  PublicSubnetB:
    Type: AWS::EC2::Subnet
    Properties:
      VpcId: !Ref VPC
      AvailabilityZone: us-east-1b
      CidrBlock: 10.0.2.0/24
      MapPublicIpOnLaunch: true
      Tags:
        - Key: Name
          Value: !Sub '${EnvironmentName}-public-b'

  # ============================================================
  # Route Table — directs traffic to the internet gateway
  # ============================================================
  PublicRouteTable:
    Type: AWS::EC2::RouteTable
    Properties:
      VpcId: !Ref VPC
      Tags:
        - Key: Name
          Value: !Sub '${EnvironmentName}-public-rt'

  # Default route: all traffic (0.0.0.0/0) goes to the IGW
  DefaultPublicRoute:
    Type: AWS::EC2::Route
    DependsOn: InternetGatewayAttachment
    Properties:
      RouteTableId: !Ref PublicRouteTable
      DestinationCidrBlock: 0.0.0.0/0
      GatewayId: !Ref InternetGateway

  # Associate subnets with the route table
  PublicSubnetARouteTableAssociation:
    Type: AWS::EC2::SubnetRouteTableAssociation
    Properties:
      SubnetId: !Ref PublicSubnetA
      RouteTableId: !Ref PublicRouteTable

  PublicSubnetBRouteTableAssociation:
    Type: AWS::EC2::SubnetRouteTableAssociation
    Properties:
      SubnetId: !Ref PublicSubnetB
      RouteTableId: !Ref PublicRouteTable

  # ============================================================
  # Security Groups — virtual firewalls
  # ============================================================

  # ALB Security Group: Allow HTTP/HTTPS from the internet
  ALBSecurityGroup:
    Type: AWS::EC2::SecurityGroup
    Properties:
      GroupDescription: ALB - allow HTTP and HTTPS from internet
      VpcId: !Ref VPC
      SecurityGroupIngress:
        - IpProtocol: tcp
          FromPort: 80
          ToPort: 80
          CidrIp: 0.0.0.0/0           # HTTP from anywhere
        - IpProtocol: tcp
          FromPort: 443
          ToPort: 443
          CidrIp: 0.0.0.0/0           # HTTPS from anywhere
      Tags:
        - Key: Name
          Value: !Sub '${EnvironmentName}-alb-sg'

  # ECS Task Security Group: Allow traffic only from the ALB
  TaskSecurityGroup:
    Type: AWS::EC2::SecurityGroup
    Properties:
      GroupDescription: ECS tasks - allow traffic from ALB only
      VpcId: !Ref VPC
      SecurityGroupIngress:
        - IpProtocol: tcp
          FromPort: 8080               # API nginx port
          ToPort: 8080
          SourceSecurityGroupId: !Ref ALBSecurityGroup
        - IpProtocol: tcp
          FromPort: 3000               # Frontend port
          ToPort: 3000
          SourceSecurityGroupId: !Ref ALBSecurityGroup
      Tags:
        - Key: Name
          Value: !Sub '${EnvironmentName}-task-sg'

  # RDS Security Group: Allow PostgreSQL only from ECS tasks
  RDSSecurityGroup:
    Type: AWS::EC2::SecurityGroup
    Properties:
      GroupDescription: RDS - allow PostgreSQL from ECS tasks only
      VpcId: !Ref VPC
      SecurityGroupIngress:
        - IpProtocol: tcp
          FromPort: 5432
          ToPort: 5432
          SourceSecurityGroupId: !Ref TaskSecurityGroup
      Tags:
        - Key: Name
          Value: !Sub '${EnvironmentName}-rds-sg'

Outputs:
  VPCId:
    Value: !Ref VPC
  PublicSubnetAId:
    Value: !Ref PublicSubnetA
  PublicSubnetBId:
    Value: !Ref PublicSubnetB
  ALBSecurityGroupId:
    Value: !Ref ALBSecurityGroup
  TaskSecurityGroupId:
    Value: !Ref TaskSecurityGroup
  RDSSecurityGroupId:
    Value: !Ref RDSSecurityGroup
```

---

## Deploy

> **Stack assignment:** This VPC stack becomes part of the `aplika-persistent` stack in the two-stack sleep/wake model (Chapter 13). The VPC costs $0 and persists across sleep/wake cycles.

```bash
aws cloudformation deploy \
  --template-file vpc.yaml \
  --stack-name aplika-vpc \
  --region us-east-1 \
  --capabilities CAPABILITY_IAM
```

> **Exam note:** `CAPABILITY_IAM` is needed when the template creates IAM resources. For VPC-only templates, it's not required — but it doesn't hurt to include it.

### Verify

```bash
# Check stack status
aws cloudformation describe-stacks \
  --stack-name aplika-vpc \
  --query 'Stacks[0].StackStatus'
# Expected: CREATE_COMPLETE

# List VPCs
aws ec2 describe-vpcs --filters "Name=tag:Name,Values=aplika-vpc" \
  --query 'Vpcs[].[VpcId,CidrBlock]' --output table

# List subnets
aws ec2 describe-subnets --filters "Name=tag:Name,Values=aplika-*" \
  --query 'Subnets[].[SubnetId,AvailabilityZone,CidrBlock,MapPublicIpOnLaunch]' --output table
```

---

## Security Group Deep Dive

Security groups are **stateful** firewalls. Key properties:

| Property | Behavior |
|----------|----------|
| **Stateful** | If you allow inbound, the response is automatically allowed outbound |
| **Allow only** | No explicit deny rules — only allow rules (default: deny all) |
| **Reference other SGs** | You can allow traffic from another security group by ID |
| **No blocked ports** | All outbound traffic is allowed by default |

Our security group chain:

```
Internet → ALB SG (80, 443) → Task SG (8080, 3000) → RDS SG (5432)
```

Each layer only accepts traffic from the previous layer. This is the **defense in depth** principle.

> **Exam note:** Security groups vs NACLs:
> - **Security groups:** Instance-level, stateful, allow-only
> - **NACLs:** Subnet-level, stateless, allow AND deny
> - Security groups are more common on the exam

---

## DNS Resolution Inside the VPC

With `EnableDnsSupport: true` and `EnableDnsHostnames: true`, instances in the VPC can resolve DNS names. This is critical for:

- RDS endpoints (e.g., `aplika-db.xxxxx.us-east-1.rds.amazonaws.com`)
- SQS queue URLs
- SSM Parameter Store API calls
- ECS service discovery (if used)

---

## Theory: Private Subnets + NAT Gateway

In production, you'd put ECS tasks and RDS in **private subnets** (no direct internet access) and use a **NAT gateway** for outbound traffic (e.g., pulling images from ECR, calling SES API).

### Why Not Here?

A NAT gateway costs ~$32/month ($0.045/hour + data processing). For a learning project, public subnets with security groups provide adequate protection at $0 extra cost.

### The Pattern (For Reference)

```
Public Subnet:
  └── NAT Gateway (has public IP, routes to IGW)

Private Subnet:
  └── ECS Tasks (no public IP)
      └── Route table: 0.0.0.0/0 → NAT Gateway
```

### When to Add NAT

- When you need compliance (no public IPs on application servers)
- When you process sensitive data (PII/GDPR)
- When security auditors require it
- When your budget allows ($32+/month)

---

## Developer Associate Exam Mapping

| Concept | Why It's on the Exam |
|---------|---------------------|
| VPC CIDR blocks | Network design |
| Public vs private subnets | Network architecture |
| Internet gateway | VPC internet connectivity |
| Route tables | Traffic routing |
| Security groups | Instance-level firewall |
| NACLs | Subnet-level firewall |
| NAT gateways | Private subnet internet access |
| DNS in VPC | Service discovery |

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| Can't reach internet from subnet | Missing route to IGW | Check route table association |
| ECS tasks can't connect to RDS | Security group rules | Verify RDS SG allows traffic from Task SG |
| `MapPublicIpOnLaunch` is false | Subnet not configured | Set `MapPublicIpOnLaunch: true` on public subnets |
| CFN deploy fails | Circular dependency | Check `DependsOn` for IGW attachment |

## Next Step

[Chapter 04: RDS PostgreSQL](04-rds-postgres.md) — create the managed database.
