# Chapter 04: RDS PostgreSQL

> Create a managed PostgreSQL database with RDS. This chapter naturally completes the "Create RDS" credit side-quest.

**Estimated duration:** 30-60 minutes | **Cost:** ~$12.20/month (db.t4g.micro, Single-AZ)

---

## What is RDS?

**Amazon RDS (Relational Database Service)** is a managed database service. AWS handles provisioning, patching, backups, and failure recovery. You focus on schema and queries.

> **Exam note:** RDS is heavily tested. Know: instance classes, Multi-AZ vs read replicas, automated backups, parameter groups, and subnet groups.

## Why db.t4g.micro?

| Property | Value | Why |
|----------|-------|-----|
| Instance class | db.t4g.micro | Cheapest current-gen; 2 vCPU, 1 GiB RAM |
| Engine | PostgreSQL 16 | Matches our local dev stack |
| Architecture | ARM64 (Graviton2) | 20% cheaper than x86 equivalent |
| Deployment | Single-AZ | Saves ~50% vs Multi-AZ; acceptable for learning |
| Storage | 20 GB gp3 | General purpose SSD; sufficient for learning |

> **Exam note:** `t4g` instances use AWS Graviton (ARM) processors. They're burstable — you accumulate CPU credits during idle time and spend them during spikes. If credits run out, CPU is throttled.

---

## CloudFormation Template

```yaml
# rds.yaml
AWSTemplateFormatVersion: '2010-09-09'
Description: Aplika RDS PostgreSQL instance

Parameters:
  EnvironmentName:
    Type: String
    Default: aplika
  DBPassword:
    Type: String
    NoEcho: true                    # Masks the value in CloudFormation console
    Description: Master database password
    MinLength: 8
    MaxLength: 41

Resources:
  # ============================================================
  # DB Subnet Group — tells RDS which subnets the DB can live in
  # Must span at least 2 AZs for RDS to accept the group.
  # ============================================================
  DBSubnetGroup:
    Type: AWS::RDS::DBSubnetGroup
    Properties:
      DBSubnetGroupDescription: Aplika database subnets
      SubnetIds:
        - !ImportValue aplika-vpc-PublicSubnetAId
        - !ImportValue aplika-vpc-PublicSubnetBId
      Tags:
        - Key: Name
          Value: !Sub '${EnvironmentName}-db-subnet-group'

  # ============================================================
  # RDS Instance — PostgreSQL 16, Single-AZ, smallest size
  # ============================================================
  DBInstance:
    Type: AWS::RDS::DBInstance
    Properties:
      DBInstanceIdentifier: !Sub '${EnvironmentName}-db'
      Engine: postgres
      EngineVersion: '16'
      DBInstanceClass: db.t4g.micro
      AllocatedStorage: 20           # GB
      StorageType: gp3
      StorageEncrypted: true         # Always encrypt at rest
      MasterUsername: aplika
      MasterUserPassword: !Ref DBPassword
      DBSubnetGroupName: !Ref DBSubnetGroup
      VPCSecurityGroups:
        - !ImportValue aplika-vpc-RDSSecurityGroupId
      PubliclyAccessible: true       # In public subnet; protected by SG
      MultiAZ: false                 # Single-AZ to save cost
      BackupRetentionPeriod: 7       # Days of automated backups
      DeletionProtection: false      # Set to true in real production
      CopyTagsToSnapshot: true
      Tags:
        - Key: Name
          Value: !Sub '${EnvironmentName}-db'

Outputs:
  DBEndpoint:
    Value: !GetAtt DBInstance.Endpoint.Address
    Description: RDS endpoint hostname
  DBPort:
    Value: !GetAtt DBInstance.Endpoint.Port
    Description: RDS port
```

### Key Properties Explained

| Property | What It Does | Why We Set It |
|----------|-------------|---------------|
| `StorageEncrypted: true` | Encrypts data at rest with KMS | Security best practice; no performance impact |
| `PubliclyAccessible: true` | Assigns a public IP | We're in a public subnet; SG restricts access |
| `BackupRetentionPeriod: 7` | Keeps 7 days of automated backups | Minimum for point-in-time recovery |
| `DeletionProtection: false` | Allows deletion | Set `true` in production to prevent accidents |
| `NoEcho: true` | Hides password in CFN console | Security — password won't appear in logs |

---

## Deploy

> **Stack assignment:** The RDS instance goes into the `aplika-runtime` stack (Chapter 13). It uses `DeletionPolicy: Snapshot` — when the runtime stack is deleted for sleep, a final snapshot is created automatically. The RDS security group stays in the `aplika-persistent` VPC stack. SSM parameters are also persistent.

```bash
# Generate a strong password
DB_PASSWORD=$(openssl rand -base64 24 | tr -d '/+=' | head -c 32)
echo "Generated password: $DB_PASSWORD"
echo "SAVE THIS PASSWORD — you'll need it for DATABASE_URL"

# Deploy
aws cloudformation deploy \
  --template-file rds.yaml \
  --stack-name aplika-rds \
  --region us-east-1 \
  --parameter-overrides DBPassword="$DB_PASSWORD" \
  --capabilities CAPABILITY_IAM
```

### Store the Password in SSM Parameter Store

```bash
# Store the password securely (encrypted with KMS)
aws ssm put-parameter \
  --name "/aplika/prod/database-password" \
  --value "$DB_PASSWORD" \
  --type SecureString \
  --region us-east-1

# Get the RDS endpoint
DB_ENDPOINT=$(aws cloudformation describe-stacks \
  --stack-name aplika-rds \
  --query 'Stacks[0].Outputs[?OutputKey==`DBEndpoint`].OutputValue' \
  --output text)

echo "RDS Endpoint: $DB_ENDPOINT"

# Store the full DATABASE_URL
aws ssm put-parameter \
  --name "/aplika/prod/database-url" \
  --value "postgresql://aplika:${DB_PASSWORD}@${DB_ENDPOINT}:5432/aplika?serverVersion=16&charset=utf8" \
  --type SecureString \
  --region us-east-1
```

> **Exam note:** SSM Parameter Store is a key-value store for configuration and secrets. `SecureString` parameters are encrypted with KMS. It's cheaper than Secrets Manager and sufficient for this use case. Secrets Manager adds automatic rotation — worth it for production databases.

### Credit Side-Quest #2: Create RDS (+$20) ✅

By creating this RDS instance, you've earned $20 in credits!

---

## Verify

```bash
# Check RDS status
aws rds describe-db-instances \
  --db-instance-identifier aplika-db \
  --query 'DBInstances[].[DBInstanceStatus,Endpoint.Address,Engine,EngineVersion]' \
  --output table

# Wait for status "available" (takes 5-10 minutes)
aws rds wait db-instance-available --db-instance-identifier aplika-db

# Test connection (requires psql installed)
psql "postgresql://aplika:$DB_PASSWORD@$DB_ENDPOINT:5432/aplika" -c "SELECT version();"
```

Expected: PostgreSQL 16.x

---

## Run Migrations

Once RDS is available, run Doctrine migrations via the API container:

```bash
# Set the DATABASE_URL to point to RDS
export DATABASE_URL="postgresql://aplika:$DB_PASSWORD@$DB_ENDPOINT:5432/aplika?serverVersion=16&charset=utf8"

# Run migrations (from local Docker, pointing to RDS)
docker compose exec -T api \
  env DATABASE_URL="$DATABASE_URL" \
  php bin/console doctrine:migrations:migrate --no-interaction
```

---

## Automated Backups

RDS takes daily automated snapshots during a 30-minute backup window. Key properties:

| Property | Our Setting | Notes |
|----------|-------------|-------|
| Retention | 7 days | Minimum for point-in-time recovery |
| Backup window | System-chosen | AWS picks a 30-min window |
| Storage | Included in gp3 cost | Snapshots up to DB size are free |

> **Exam note:** Automated backups enable **point-in-time recovery** — you can restore to any second within the retention period. Manual snapshots are separate and don't auto-delete.

---

## Single-AZ vs Multi-AZ

| Aspect | Single-AZ | Multi-AZ |
|--------|-----------|----------|
| Cost | $12.20/mo | ~$24.40/mo |
| Failover | Manual | Automatic (60-120s) |
| Standby | None | Synchronous replica in different AZ |
| Use case | Dev/test, learning | Production, high availability |

**Why Single-AZ?** For a learning project, automatic failover isn't worth doubling the cost. Upgrade to Multi-AZ when deploying real user data.

---

## Developer Associate Exam Mapping

| Concept | Why It's on the Exam |
|---------|---------------------|
| RDS instance classes | Choosing the right size |
| Single-AZ vs Multi-AZ | High availability |
| Automated backups | Disaster recovery |
| Parameter groups | Database configuration |
| Subnet groups | Network placement |
| Storage encryption | Security |
| SSM Parameter Store | Secrets management |

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| RDS creation takes 10+ min | Normal — RDS provisions hardware | Wait; check status with `describe-db-instances` |
| Can't connect from local machine | Security group or public IP | Verify RDS SG allows your IP; `PubliclyAccessible: true` |
| "Password rejected" | Special characters in password | Use alphanumeric only; URL-encode if needed |
| Migrations fail | RDS not ready | Wait for status "available"; check `DATABASE_URL` |

## Next Step

[Chapter 05: ECR & Images](05-ecr-and-images.md) — create container repositories and push production images.
