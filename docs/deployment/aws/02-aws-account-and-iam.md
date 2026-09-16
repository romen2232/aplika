# Chapter 02: AWS Account & IAM

> Create your AWS account, choose between Free Plan and Paid Plan, secure the root user, set up IAM for CLI access, and complete credit-earning activities.

**Estimated duration:** 1-2 hours | **Cost:** $0 (uses Free Tier credits)

---

## 1. AWS Account Signup

### Free Plan vs Paid Plan (Post-July 15, 2025)

AWS changed its Free Tier program on July 15, 2025. New accounts now choose between two plans:

| Aspect | Free Plan | Paid Plan |
|--------|-----------|-----------|
| **Duration** | 6 months max | Indefinite |
| **Initial credits** | $100 | $100 |
| **Bonus credits** | Up to $100 more | Up to $100 more |
| **Always Free services** | Yes (30+) | Yes (30+) |
| **Short-term trials** | No | Yes |
| **12-month free tier** | No | No |
| **When credits run out** | Account closes | Pay-as-you-go |
| **Service access** | Curated list only | All AWS services |

### Our Stack's Free Plan Eligibility

| Service | Free Plan? | Notes |
|---------|-----------|-------|
| ECS | ✅ Eligible | Listed under Compute |
| **Fargate** | ❌ **No free tier** | Draws from credits; no always-free allowance |
| ALB | ✅ Eligible | Use credits |
| RDS (db.t4g.micro) | ✅ Eligible | Use credits |
| ECR | ✅ Eligible | Use credits |
| Route 53 | ✅ Eligible | $0.50/mo hosted zone |
| SQS | ✅ Always Free | 1M requests/month |
| SES | ✅ Eligible | Use credits |
| CloudWatch | ✅ Eligible | 10 custom metrics always free |
| ACM (certificates) | ✅ Always Free | Public SSL certs are free |
| SSM Parameter Store | ✅ Always Free | Standard parameters free |

### Decision Rule

**Choose the Paid Plan.** Here's why:

1. Fargate has no free tier — it draws from credits regardless of plan.
2. The Free Plan auto-closes after 6 months — you lose everything.
3. The Paid Plan lets you keep running after credits expire (you just pay).
4. Both plans get the same $200 in credits.
5. The Paid Plan gives access to short-term trials for additional services.

> **Exam note:** The new Free Tier model is credit-based, not time-based (for new accounts). Credits expire 12 months after account creation. The Free Plan is a "trial" — it auto-closes. The Paid Plan is "standard" — pay-as-you-go after credits.

### Signup Steps

1. Go to [aws.amazon.com](https://aws.amazon.com)
2. Click **Create an AWS Account**
3. Enter your email, password, and account name
4. **Choose Paid Plan** (Standard)
5. Enter payment information (credit card — won't be charged until credits expire)
6. Verify your identity (phone or email)
7. Select **Basic Support** (free)

### Credit Side-Quest #1: Sign Up ($100 earned)

The $100 signup credit is automatic. You now have $200 total ($100 signup + up to $100 earned).

---

## 2. Root User Hardening

The **root user** is the account owner — it has unlimited access to everything. Never use it for daily work.

> **Exam note:** Root user security is exam-relevant. Know that root has full access, MFA should be enabled, and IAM users/roles should be used instead.

### Enable MFA (Multi-Factor Authentication)

1. Sign in as root user
2. Go to **IAM** → **Dashboard** → **Security Status**
3. Click **Activate MFA on root account**
4. Choose **Virtual MFA device** (use Google Authenticator or Authy)
5. Scan the QR code, enter two consecutive codes
6. Click **Assign MFA**

### Create an IAM Admin User

Never use root for day-to-day work. Create an admin IAM user:

1. Go to **IAM** → **Users** → **Create user**
2. Username: `aplika-admin`
3. Check **Provide user access to the AWS Management Console**
4. Choose **I want to create an IAM user**
5. Set a strong password
6. Click **Next**
7. On **Set permissions**, choose **Attach policies directly**
8. Search for and select **AdministratorAccess**
9. Click **Create user**
10. Download the `.csv` with credentials
11. **Sign out of root, sign in as `aplika-admin`**

> **Why AdministratorAccess?** For a learning project, full admin is acceptable. In production, you'd create separate IAM users with least-privilege policies. We'll create a CLI user with limited permissions next.

---

## 3. IAM User for CLI Access

### Create a CLI User

1. Go to **IAM** → **Users** → **Create user`
2. Username: `aplika-cli`
3. **Do NOT** check console access (CLI-only)
4. Click **Next**
5. **Attach policies directly** → select **AdministratorAccess** (for learning)
6. Click **Create user`
7. Go to the user → **Security credentials** tab
8. Click **Create access key`
9. Choose **CLI**
10. Download the access key CSV

### Configure AWS CLI

```bash
# Install AWS CLI (if not installed)
curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip"
unzip awscliv2.zip && sudo ./aws/install

# Configure credentials
aws configure
# AWS Access Key ID: [from CSV]
# AWS Secret Access Key: [from CSV]
# Default region name: us-east-1
# Default output format: json

# Verify
aws sts get-caller-identity
```

Expected output:
```json
{
    "UserId": "AIDAEXAMPLEID",
    "Account": "123456789012",
    "Arn": "arn:aws:iam::123456789012:user/aplika-cli"
}
```

### IAM Best Practices (Exam Core)

> **Exam note:** IAM is tested heavily. Know these principles:

| Principle | What It Means |
|-----------|---------------|
| **Least privilege** | Grant only the permissions needed. We used Admin for learning — production needs scoped policies. |
| **No long-lived keys for CI/CD** | Use IAM roles or OIDC (Chapter 10). |
| **MFA everywhere** | Enable MFA on root and all human IAM users. |
| **Rotate credentials** | Access keys should be rotated every 90 days. |
| **Use roles for EC2/ECS** | Never put access keys in containers. Use task roles. |

---

## 4. Region Choice: us-east-1

**Why us-east-1 (N. Virginia)?**

| Factor | us-east-1 | EU regions |
|--------|-----------|------------|
| Price | Cheapest (baseline) | 5-15% more |
| Service availability | First to get new services | Sometimes delayed |
| Latency from Spain | ~120ms | ~30ms |
| Exam relevance | Most exam questions reference us-east-1 | — |

**Latency tradeoff:** ~120ms from Spain to us-east-1 vs ~30ms to eu-west-1 (Ireland). For a learning project, this is acceptable. For production with users in Europe, use eu-west-1.

Set your default:
```bash
aws configure set region us-east-1
```

---

## 5. Credit Side-Quests ($20 each)

Complete these to earn up to $100 in additional credits. Each one also practices an exam-relevant service.

### Side-Quest #1: Launch an EC2 Instance (+$20)

> **Exam topic:** EC2 instance types, launch, termination.

```bash
# Launch a t3.micro (Free Plan eligible)
aws ec2 run-instances \
  --image-id ami-0abcdef1234567890 \
  --instance-type t3.micro \
  --key-name aplika-test \
  --region us-east-1

# List running instances
aws ec2 describe-instances --query 'Reservations[].Instances[].[InstanceId,State.Name,InstanceType]' --output table

# Terminate (important — don't leave it running!)
aws ec2 terminate-instances --instance-ids i-xxxxxxxxx
```

**What you learned:** EC2 instance lifecycle (pending → running → shutting-down → terminated), AMI IDs, key pairs.

### Side-Quest #2: Create an RDS Database (+$20)

This happens naturally in Chapter 04. You'll create a `db.t4g.micro` PostgreSQL instance.

### Side-Quest #3: Deploy a Lambda Function (+$20)

> **Exam topic:** Lambda runtime, handler, invocation.

```bash
# Create a simple Lambda function
cat > /tmp/lambda_function.py << 'EOF'
def handler(event, context):
    return {
        'statusCode': 200,
        'body': 'Hello from Aplika Lambda!'
    }
EOF

cd /tmp && zip function.zip lambda_function.py

aws lambda create-function \
  --function-name aplika-hello \
  --runtime python3.12 \
  --role arn:aws:iam::123456789012:role/lambda-basic-execution \
  --handler lambda_function.handler \
  --zip-file fileb://function.zip

# Invoke
aws lambda invoke --function-name aplika-hello response.json
cat response.json

# Clean up
aws lambda delete-function --function-name aplika-hello
```

**What you learned:** Lambda runtime, handler function, IAM execution role, invocation.

### Side-Quest #4: Try Amazon Bedrock (+$20)

> **Exam topic:** Bedrock foundation models, inference.

1. Go to **Amazon Bedrock** console
2. Click **Get started** → **Enable model access**
3. Enable **Claude 3 Haiku** (or any free-tier model)
4. Go to **Playground** → **Chat**
5. Send a prompt: "Explain SQS dead-letter queues in 2 sentences"
6. You've earned $20!

**What you learned:** Bedrock model access, inference playground.

### Side-Quest #5: Set Up AWS Budgets (+$20)

> **Exam topic:** Budgets, cost monitoring.

```bash
aws budgets create-budget \
  --account-id 123456789012 \
  --budget '{
    "BudgetName": "aplika-monthly",
    "BudgetLimit": {
      "Amount": "50",
      "Unit": "USD"
    },
    "BudgetType": "COST",
    "TimeUnit": "MONTHLY"
  }' \
  --notifications-with-subscribers '[
    {
      "Notification": {
        "NotificationType": "ACTUAL",
        "ComparisonOperator": "GREATER_THAN",
        "Threshold": 80,
        "ThresholdType": "PERCENTAGE"
      },
      "Subscribers": [{
        "SubscriptionType": "EMAIL",
        "Address": "your-email@example.com"
      }]
    }
  ]'
```

**What you learned:** Budgets, thresholds, notifications, cost monitoring.

### Bonus: Sleep-Mode Budget Alarm

Set a second budget to catch forgotten running stacks. If you accidentally leave the stack running while sleeping, this alarm fires:

```bash
aws budgets create-budget \
  --account-id $(aws sts get-caller-identity --query Account --output text) \
  --budget '{
    "BudgetName": "aplika-sleep-guard",
    "BudgetLimit": {"Amount": "10", "Unit": "USD"},
    "BudgetType": "COST",
    "TimeUnit": "MONTHLY"
  }' \
  --notifications-with-subscribers '[
    {
      "Notification": {
        "NotificationType": "ACTUAL",
        "ComparisonOperator": "GREATER_THAN",
        "Threshold": 10,
        "ThresholdType": "PERCENTAGE"
      },
      "Subscribers": [{
        "SubscriptionType": "EMAIL",
        "Address": "your-email@example.com"
      }]
    }
  ]'
```

**Why $10?** In sleeping mode, costs should be ~$1-3/mo. If your bill exceeds $10, you likely forgot to `make sleep`. This catches the mistake before it drains your credits.

---

## Verification

```bash
# Confirm CLI access
aws sts get-caller-identity

# Confirm region
aws configure get region
# Expected: us-east-1

# Confirm credits (check Billing dashboard in console)
# You should see $200 in available credits
```

---

## Developer Associate Exam Mapping

| Concept | Why It's on the Exam |
|---------|---------------------|
| IAM users, groups, policies | Tested in every exam domain |
| Least privilege principle | Core IAM concept |
| MFA | Security best practice |
| Root user restrictions | Account security |
| Access keys vs roles | Credential management |
| EC2 instance lifecycle | Compute domain |
| Lambda basics | Serverless domain |
| Budgets | Cost management domain |

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| `aws sts get-caller-identity` fails | Wrong credentials | Re-run `aws configure` |
| Can't create resources in us-east-1 | Account not activated | Wait 24 hours or contact AWS support |
| Credits not showing | Credits take up to 24 hours to appear | Wait, then check Billing dashboard |
| MFA not working | Time sync issue | Sync your phone's clock |

## Next Step

[Chapter 03: VPC & Networking](03-vpc-and-networking.md) — create the network foundation for all AWS resources.
