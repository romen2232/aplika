# Chapter 06: ECS Services

> Create the ECS cluster, task definitions for all three services, and configure the ALB with target groups and health checks.

**Estimated duration:** 2-3 hours | **Cost:** ~$47/month (3 Fargate tasks + ALB)

---

## What is ECS?

**Amazon ECS (Elastic Container Service)** is a container orchestration service. It runs and manages Docker containers on a cluster.

> **Exam note:** ECS is a core exam topic. Know: clusters, task definitions, services, launch types (Fargate vs EC2), task roles vs execution roles, and service auto scaling.

**Key concepts:**

| Concept | What It Is |
|---------|-----------|
| **Cluster** | A logical grouping of resources where tasks run |
| **Task Definition** | A blueprint for your application (like a Docker Compose service) |
| **Task** | A running instance of a task definition |
| **Service** | Maintains a desired number of tasks running (auto-restart, rolling updates) |
| **Fargate** | Serverless compute — no EC2 instances to manage |

---

## Task Role vs Execution Role

This is a **favorite exam topic**. Understand the difference:

| Role | When It's Used | What It Can Do |
|------|---------------|----------------|
| **Execution Role** | At task START time | Pull images from ECR, push logs to CloudWatch |
| **Task Role** | While task is RUNNING | Application permissions (SQS, SES, SSM, S3) |

> **Exam tip:** The execution role is used by the ECS agent (pulling images, sending logs). The task role is assumed by the application code (reading from SQS, writing to S3). Never confuse them.

---

## CloudFormation Template

```yaml
# ecs.yaml
AWSTemplateFormatVersion: '2010-09-09'
Description: Aplika ECS Cluster, Task Definitions, and Services

Parameters:
  EnvironmentName:
    Type: String
    Default: aplika
  ECRRepositoryPrefix:
    Type: String
    Description: ECR registry prefix (account-id.dkr.ecr.us-east-1.amazonaws.com/aplika)
  ImageTag:
    Type: String
    Default: latest
    Description: Docker image tag to deploy

Resources:
  # ============================================================
  # ECS Cluster — the logical boundary for all tasks
  # ============================================================
  ECSCluster:
    Type: AWS::ECS::Cluster
    Properties:
      ClusterName: !Sub '${EnvironmentName}-cluster'
      ClusterSettings:
        - Name: containerInsights
          Value: enabled              # Enables CloudWatch Container Insights

  # ============================================================
  # Execution Role — used by ECS agent to pull images and send logs
  # ============================================================
  ExecutionRole:
    Type: AWS::IAM::Role
    Properties:
      RoleName: !Sub '${EnvironmentName}-execution-role'
      AssumeRolePolicyDocument:
        Version: '2012-10-17'
        Statement:
          - Effect: Allow
            Principal:
              Service: ecs-tasks.amazonaws.com
            Action: sts:AssumeRole
      ManagedPolicyArns:
        - arn:aws:iam::aws:policy/service-role/AmazonECSTaskExecutionRolePolicy
      Policies:
        - PolicyName: SSMReadAccess
          PolicyDocument:
            Version: '2012-10-17'
            Statement:
              - Effect: Allow
                Action:
                  - ssm:GetParameters
                  - ssm:GetParameter
                Resource: !Sub 'arn:aws:ssm:${AWS::Region}:${AWS::AccountId}:parameter/aplika/prod/*'

  # ============================================================
  # Task Role — assumed by the running application
  # ============================================================
  TaskRole:
    Type: AWS::IAM::Role
    Properties:
      RoleName: !Sub '${EnvironmentName}-task-role'
      AssumeRolePolicyDocument:
        Version: '2012-10-17'
        Statement:
          - Effect: Allow
            Principal:
              Service: ecs-tasks.amazonaws.com
            Action: sts:AssumeRole
      Policies:
        - PolicyName: SQSAccess
          PolicyDocument:
            Version: '2012-10-17'
            Statement:
              - Effect: Allow
                Action:
                  - sqs:SendMessage
                  - sqs:ReceiveMessage
                  - sqs:DeleteMessage
                  - sqs:GetQueueAttributes
                Resource: !Sub 'arn:aws:sqs:${AWS::Region}:${AWS::AccountId}:${EnvironmentName}-*'
        - PolicyName: SESAccess
          PolicyDocument:
            Version: '2012-10-17'
            Statement:
              - Effect: Allow
                Action:
                  - ses:SendEmail
                  - ses:SendRawEmail
                Resource: '*'

  # ============================================================
  # CloudWatch Log Groups — one per service
  # ============================================================
  APILogGroup:
    Type: AWS::Logs::LogGroup
    Properties:
      LogGroupName: !Sub '/ecs/${EnvironmentName}-api'
      RetentionInDays: 14

  WorkerLogGroup:
    Type: AWS::Logs::LogGroup
    Properties:
      LogGroupName: !Sub '/ecs/${EnvironmentName}-worker'
      RetentionInDays: 14

  FrontendLogGroup:
    Type: AWS::Logs::LogGroup
    Properties:
      LogGroupName: !Sub '/ecs/${EnvironmentName}-frontend'
      RetentionInDays: 14

  # ============================================================
  # Task Definitions — blueprints for each service
  # ============================================================

  # API Task Definition (nginx + php-fpm in one container)
  APITaskDefinition:
    Type: AWS::ECS::TaskDefinition
    Properties:
      Family: !Sub '${EnvironmentName}-api'
      NetworkMode: awsvpc             # Required for Fargate
      RequiresCompatibilities:
        - FARGATE
      Cpu: '512'                      # 0.5 vCPU
      Memory: '1024'                  # 1 GB
      ExecutionRoleArn: !Ref ExecutionRole
      TaskRoleArn: !Ref TaskRole
      ContainerDefinitions:
        - Name: api
          Image: !Sub '${ECRRepositoryPrefix}/aplika/api:${ImageTag}'
          PortMappings:
            - ContainerPort: 8080
          Environment:
            - Name: APP_ENV
              Value: prod
            - Name: MESSENGER_TRANSPORT_DSN
              Value: !Sub 'sqs://default?queue_name=${EnvironmentName}-messages'
            - Name: MAILER_DSN
              Value: !Sub 'ses+api://default?region=${AWS::Region}'
            - Name: DEFAULT_URI
              Value: https://api.aplika.work
            - Name: CORS_ALLOW_ORIGIN
              Value: '^https://(app\.aplika\.work|api\.aplika\.work)$'
            - Name: TRUSTED_PROXIES
              Value: '10.0.0.0/16'
          Secrets:
            - Name: APP_SECRET
              ValueFrom: !Sub 'arn:aws:ssm:${AWS::Region}:${AWS::AccountId}:parameter/aplika/prod/app-secret'
            - Name: DATABASE_URL
              ValueFrom: !Sub 'arn:aws:ssm:${AWS::Region}:${AWS::AccountId}:parameter/aplika/prod/database-url'
            - Name: JWT_SECRET_KEY
              ValueFrom: !Sub 'arn:aws:ssm:${AWS::Region}:${AWS::AccountId}:parameter/aplika/prod/jwt-secret'
          LogConfiguration:
            LogDriver: awslogs
            Options:
              awslogs-group: !Ref APILogGroup
              awslogs-region: !Ref AWS::Region
              awslogs-stream-prefix: ecs

  # Worker Task Definition (same image, different command)
  WorkerTaskDefinition:
    Type: AWS::ECS::TaskDefinition
    Properties:
      Family: !Sub '${EnvironmentName}-worker'
      NetworkMode: awsvpc
      RequiresCompatibilities:
        - FARGATE
      Cpu: '256'                      # 0.25 vCPU
      Memory: '512'                   # 0.5 GB
      ExecutionRoleArn: !Ref ExecutionRole
      TaskRoleArn: !Ref TaskRole
      ContainerDefinitions:
        - Name: worker
          Image: !Sub '${ECRRepositoryPrefix}/aplika/api:${ImageTag}'
          Command:
            - php
            - bin/console
            - messenger:consume
            - async
            - --time-limit=3600
            - --memory-limit=128M
          Environment:
            - Name: APP_ENV
              Value: prod
            - Name: MESSENGER_TRANSPORT_DSN
              Value: !Sub 'sqs://default?queue_name=${EnvironmentName}-messages'
            - Name: MAILER_DSN
              Value: !Sub 'ses+api://default?region=${AWS::Region}'
          Secrets:
            - Name: APP_SECRET
              ValueFrom: !Sub 'arn:aws:ssm:${AWS::Region}:${AWS::AccountId}:parameter/aplika/prod/app-secret'
            - Name: DATABASE_URL
              ValueFrom: !Sub 'arn:aws:ssm:${AWS::Region}:${AWS::AccountId}:parameter/aplika/prod/database-url'
            - Name: JWT_SECRET_KEY
              ValueFrom: !Sub 'arn:aws:ssm:${AWS::Region}:${AWS::AccountId}:parameter/aplika/prod/jwt-secret'
          LogConfiguration:
            LogDriver: awslogs
            Options:
              awslogs-group: !Ref WorkerLogGroup
              awslogs-region: !Ref AWS::Region
              awslogs-stream-prefix: ecs

  # Frontend Task Definition
  FrontendTaskDefinition:
    Type: AWS::ECS::TaskDefinition
    Properties:
      Family: !Sub '${EnvironmentName}-frontend'
      NetworkMode: awsvpc
      RequiresCompatibilities:
        - FARGATE
      Cpu: '256'                      # 0.25 vCPU
      Memory: '512'                   # 0.5 GB
      ExecutionRoleArn: !Ref ExecutionRole
      TaskRoleArn: !Ref TaskRole
      ContainerDefinitions:
        - Name: frontend
          Image: !Sub '${ECRRepositoryPrefix}/aplika/frontend:${ImageTag}'
          PortMappings:
            - ContainerPort: 3000
          Environment:
            - Name: NODE_ENV
              Value: production
          LogConfiguration:
            LogDriver: awslogs
            Options:
              awslogs-group: !Ref FrontendLogGroup
              awslogs-region: !Ref AWS::Region
              awslogs-stream-prefix: ecs

  # ============================================================
  # Application Load Balancer — single entry point for all traffic
  # ============================================================
  ALB:
    Type: AWS::ElasticLoadBalancingV2::LoadBalancer
    Properties:
      Name: !Sub '${EnvironmentName}-alb'
      Scheme: internet-facing          # Accessible from the internet
      Type: application
      Subnets:
        - !ImportValue aplika-vpc-PublicSubnetAId
        - !ImportValue aplika-vpc-PublicSubnetBId
      SecurityGroups:
        - !ImportValue aplika-vpc-ALBSecurityGroupId

  # Target Groups — where ALB sends traffic
  APITargetGroup:
    Type: AWS::ElasticLoadBalancingV2::TargetGroup
    Properties:
      Name: !Sub '${EnvironmentName}-api-tg'
      Port: 8080
      Protocol: HTTP
      VpcId: !ImportValue aplika-vpc-VPCId
      TargetType: ip                   # Required for awsvpc/Fargate
      HealthCheckPath: /health
      HealthCheckIntervalSeconds: 30
      HealthyThresholdCount: 2
      UnhealthyThresholdCount: 3
      Matcher:
        HttpCode: '200'

  FrontendTargetGroup:
    Type: AWS::ElasticLoadBalancingV2::TargetGroup
    Properties:
      Name: !Sub '${EnvironmentName}-frontend-tg'
      Port: 3000
      Protocol: HTTP
      VpcId: !ImportValue aplika-vpc-VPCId
      TargetType: ip
      HealthCheckPath: /
      HealthCheckIntervalSeconds: 30
      HealthyThresholdCount: 2
      UnhealthyThresholdCount: 3
      Matcher:
        HttpCode: '200'

  # HTTPS Listener (will be configured in Chapter 08 with ACM cert)
  HTTPListener:
    Type: AWS::ElasticLoadBalancingV2::Listener
    Properties:
      LoadBalancerArn: !Ref ALB
      Port: 80
      Protocol: HTTP
      DefaultAction:
        - Type: forward
          TargetGroupArn: !Ref FrontendTargetGroup

  # Host-based routing rules
  APIListenerRule:
    Type: AWS::ElasticLoadBalancingV2::ListenerRule
    Properties:
      ListenerArn: !Ref HTTPListener
      Priority: 1
      Conditions:
        - Field: host-header
          Values:
            - api.aplika.work
      Actions:
        - Type: forward
          TargetGroupArn: !Ref APITargetGroup

  # ECS Services
  APIService:
    Type: AWS::ECS::Service
    DependsOn: APIListenerRule
    Properties:
      ServiceName: !Sub '${EnvironmentName}-api'
      Cluster: !Ref ECSCluster
      TaskDefinition: !Ref APITaskDefinition
      DesiredCount: 1
      LaunchType: FARGATE
      NetworkConfiguration:
        AwsvpcConfiguration:
          AssignPublicIp: ENABLED      # Public subnet — needs public IP
          SecurityGroups:
            - !ImportValue aplika-vpc-TaskSecurityGroupId
          Subnets:
            - !ImportValue aplika-vpc-PublicSubnetAId
      LoadBalancers:
        - ContainerName: api
          ContainerPort: 8080
          TargetGroupArn: !Ref APITargetGroup
      DeploymentConfiguration:
        MaximumPercent: 200
        MinimumHealthyPercent: 100

  WorkerService:
    Type: AWS::ECS::Service
    Properties:
      ServiceName: !Sub '${EnvironmentName}-worker'
      Cluster: !Ref ECSCluster
      TaskDefinition: !Ref WorkerTaskDefinition
      DesiredCount: 1
      LaunchType: FARGATE
      NetworkConfiguration:
        AwsvpcConfiguration:
          AssignPublicIp: ENABLED
          SecurityGroups:
            - !ImportValue aplika-vpc-TaskSecurityGroupId
          Subnets:
            - !ImportValue aplika-vpc-PublicSubnetAId

  FrontendService:
    Type: AWS::ECS::Service
    DependsOn: APIListenerRule
    Properties:
      ServiceName: !Sub '${EnvironmentName}-frontend'
      Cluster: !Ref ECSCluster
      TaskDefinition: !Ref FrontendTaskDefinition
      DesiredCount: 1
      LaunchType: FARGATE
      NetworkConfiguration:
        AwsvpcConfiguration:
          AssignPublicIp: ENABLED
          SecurityGroups:
            - !ImportValue aplika-vpc-TaskSecurityGroupId
          Subnets:
            - !ImportValue aplika-vpc-PublicSubnetAId
      LoadBalancers:
        - ContainerName: frontend
          ContainerPort: 3000
          TargetGroupArn: !Ref FrontendTargetGroup

Outputs:
  ALBDNSName:
    Value: !GetAtt ALB.DNSName
    Description: ALB DNS name (use for Route 53 alias)
  ClusterName:
    Value: !Ref ECSCluster
```

---

## Fargate CPU/Memory Sizing

Valid combinations (verified from AWS docs):

| CPU (vCPU) | Memory (MiB) | Our Use |
|------------|-------------|---------|
| 256 (0.25) | 512, 1024, 2048 | Worker (512), Frontend (512) |
| 512 (0.5) | 1024, 2048, 3072, 4096 | API (1024) |
| 1024 (1) | 2048-8192 | Scale up if needed |

> **Exam note:** Fargate requires CPU and memory at the TASK level (not just container level). Only specific combinations are valid — the exam may ask which combinations work.

---

## Deploy

> **Stack assignment:** ECS cluster, services, and task definitions go into the `aplika-runtime` stack. ALB, listeners, and target groups are also runtime. Execution Role and Task Role go into `aplika-persistent` (IAM is global and cheap). CloudWatch Log Groups go into `aplika-persistent` with `DeletionPolicy: Retain` to preserve logs across sleep/wake cycles. See Chapter 13 for the full two-stack architecture.

```bash
# Store remaining secrets in SSM
aws ssm put-parameter \
  --name "/aplika/prod/app-secret" \
  --value "$(openssl rand -hex 32)" \
  --type SecureString

aws ssm put-parameter \
  --name "/aplika/prod/jwt-secret" \
  --value "$(openssl rand -hex 64)" \
  --type SecureString

# Get ECR prefix
ECR_PREFIX=$(aws sts get-caller-identity --query Account --output text).dkr.ecr.us-east-1.amazonaws.com/aplika

# Deploy ECS stack
aws cloudformation deploy \
  --template-file ecs.yaml \
  --stack-name aplika-ecs \
  --region us-east-1 \
  --parameter-overrides \
    ECRRepositoryPrefix=$ECR_PREFIX \
    ImageTag=$(git rev-parse --short HEAD) \
  --capabilities CAPABILITY_NAMED_IAM
```

---

## Verify

```bash
# Check cluster
aws ecs describe-clusters --clusters aplika-cluster \
  --query 'clusters[].[clusterName,status,runningTasksCount]' --output table

# Check services
aws ecs list-services --cluster aplika-cluster --output table

# Check task health
aws ecs list-tasks --cluster aplika-cluster --service-name aplika-api \
  --query 'taskArns' --output text | xargs -I {} aws ecs describe-tasks \
  --cluster aplika-cluster --tasks {} \
  --query 'tasks[].[taskDefinitionArn,lastStatus,healthStatus]' --output table

# Check ALB target health
aws elbv2 describe-target-health \
  --target-group-arn $(aws elbv2 describe-target-groups \
    --names aplika-api-tg --query 'TargetGroups[0].TargetGroupArn' --output text) \
  --query 'TargetHealthDescriptions[].[Target.Id,TargetHealth.State]' --output table
```

---

## ECS Rolling Deployments

By default, ECS uses **rolling updates**:

1. Start a new task with the updated image
2. Wait for it to pass health checks
3. Stop an old task
4. Repeat until all tasks are updated

The `MaximumPercent: 200` and `MinimumHealthyPercent: 100` settings mean:
- At most 200% of desired tasks running (old + new during deployment)
- At least 100% of desired tasks healthy at all times

> **Exam note:** Rolling updates vs blue/green deployments:
> - **Rolling:** Default, uses ECS native, simpler
> - **Blue/green:** Uses CodeDeploy, allows instant rollback, more complex

---

## Auto Scaling Theory

For production, add auto scaling:

```yaml
# Not in our template — theory only
ScalableTarget:
  Type: AWS::ApplicationAutoScaling::ScalableTarget
  Properties:
    MaxCapacity: 4
    MinCapacity: 1
    ResourceId: !Sub 'service/${ECSCluster}/${APIService}'
    ScalableDimension: ecs:service:DesiredCount
    ServiceNamespace: ecs

ScalingPolicy:
  Type: AWS::ApplicationAutoScaling::ScalingPolicy
  Properties:
    PolicyName: cpu-scaling
    PolicyType: TargetTrackingScaling
    ScalingTargetId: !Ref ScalableTarget
    TargetTrackingScalingPolicyConfiguration:
      TargetValue: 70               # Scale when CPU hits 70%
      PredefinedMetricSpecification:
        PredefinedMetricType: ECSServiceAverageCPUUtilization
```

---

## Developer Associate Exam Mapping

| Concept | Why It's on the Exam |
|---------|---------------------|
| ECS clusters, services, tasks | Core container orchestration |
| Fargate launch type | Serverless containers |
| Task role vs execution role | IAM for containers |
| ALB target groups | Load balancing |
| Health checks | Service reliability |
| Rolling deployments | Deployment strategies |
| Auto scaling | Performance/cost optimization |

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| Task won't start | Image pull error (ECR permissions) | Check execution role has ECR access |
| Task starts then stops | Application crash | Check CloudWatch logs |
| Health check failing | Wrong port or path | Verify container port matches target group |
| `RESOURCE:ENI` error | No available IP in subnet | Use a subnet with more free IPs |
| Secrets not loading | SSM permissions | Check execution role has `ssm:GetParameters` |

## Next Step

[Chapter 07: SQS & Worker](07-sqs-and-worker.md) — create the SQS queue and configure the message worker.
