# Chapter 11: Observability

> Set up CloudWatch Logs, metric alarms, a dashboard, and the Budgets alert (which earns $20 in credits).

**Estimated duration:** 1-2 hours | **Cost:** ~$4/month

---

## CloudWatch Logs

### Log Groups and Retention

We created log groups in Chapter 06 with 14-day retention. CloudWatch Logs charges for storage beyond the free tier (5 GB/month).

> **Exam note:** CloudWatch Logs retention is per log group. Default is "Never expire" — always set a retention period to control costs.

| Log Group | Retention | Purpose |
|-----------|-----------|---------|
| `/ecs/aplika-api` | 14 days | API request logs, errors |
| `/ecs/aplika-worker` | 14 days | Message processing logs |
| `/ecs/aplika-frontend` | 14 days | Next.js server logs |

### View Logs

```bash
# Tail API logs
aws logs tail /ecs/aplika-api --follow --region us-east-1

# Search for errors
aws logs filter-log-events \
  --log-group-name /ecs/aplika-api \
  --filter-pattern "ERROR" \
  --start-time $(date -d '1 hour ago' +%s000) \
  --region us-east-1
```

---

## CloudWatch Alarms

Create alarms for critical conditions:

```yaml
# alarms.yaml
AWSTemplateFormatVersion: '2010-09-09'
Description: Aplika CloudWatch Alarms

Parameters:
  AlarmEmail:
    Type: String
    Description: Email address for alarm notifications

Resources:
  # ============================================================
  # SNS Topic — where alarm notifications are sent
  # ============================================================
  AlarmTopic:
    Type: AWS::SNS::Topic
    Properties:
      TopicName: aplika-alarms

  AlarmEmailSubscription:
    Type: AWS::SNS::Subscription
    Properties:
      TopicArn: !Ref AlarmTopic
      Protocol: email
      Endpoint: !Ref AlarmEmail

  # ============================================================
  # Alarm: ECS API task count = 0 (service is down)
  # ============================================================
  APITaskCountAlarm:
    Type: AWS::CloudWatch::Alarm
    Properties:
      AlarmName: aplika-api-no-tasks
      AlarmDescription: API service has no running tasks
      Namespace: AWS/ECS
      MetricName: RunningTaskCount
      Dimensions:
        - Name: ClusterName
          Value: aplika-cluster
        - Name: ServiceName
          Value: aplika-api
      Statistic: Average
      Period: 60
      EvaluationPeriods: 2
      Threshold: 1
      ComparisonOperator: LessThanThreshold
      TreatMissingData: breaching
      AlarmActions:
        - !Ref AlarmTopic

  # ============================================================
  # Alarm: ALB 5xx error rate > 5%
  # ============================================================
  ALB5xxAlarm:
    Type: AWS::CloudWatch::Alarm
    Properties:
      AlarmName: aplika-alb-5xx-errors
      AlarmDescription: ALB returning 5xx errors
      Namespace: AWS/ApplicationELB
      MetricName: HTTPCode_Target_5XX_Count
      Dimensions:
        - Name: LoadBalancer
          Value: !Sub '${EnvironmentName}-alb'
      Statistic: Sum
      Period: 300
      EvaluationPeriods: 1
      Threshold: 10
      ComparisonOperator: GreaterThanThreshold
      TreatMissingData: notBreaching
      AlarmActions:
        - !Ref AlarmTopic

  # ============================================================
  # Alarm: RDS CPU > 80%
  # ============================================================
  RDSCPUAlarm:
    Type: AWS::CloudWatch::Alarm
    Properties:
      AlarmName: aplika-rds-high-cpu
      AlarmDescription: RDS CPU utilization above 80%
      Namespace: AWS/RDS
      MetricName: CPUUtilization
      Dimensions:
        - Name: DBInstanceIdentifier
          Value: aplika-db
      Statistic: Average
      Period: 300
      EvaluationPeriods: 2
      Threshold: 80
      ComparisonOperator: GreaterThanThreshold
      AlarmActions:
        - !Ref AlarmTopic

  # ============================================================
  # Alarm: RDS free storage < 1 GB
  # ============================================================
  RDSStorageAlarm:
    Type: AWS::CloudWatch::Alarm
    Properties:
      AlarmName: aplika-rds-low-storage
      AlarmDescription: RDS free storage below 1 GB
      Namespace: AWS/RDS
      MetricName: FreeStorageSpace
      Dimensions:
        - Name: DBInstanceIdentifier
          Value: aplika-db
      Statistic: Average
      Period: 300
      EvaluationPeriods: 1
      Threshold: 1073741824    # 1 GB in bytes
      ComparisonOperator: LessThanThreshold
      AlarmActions:
        - !Ref AlarmTopic

  # ============================================================
  # Alarm: SQS DLQ has messages (failed processing)
  # ============================================================
  SQSDLQAlarm:
    Type: AWS::CloudWatch::Alarm
    Properties:
      AlarmName: aplika-sqs-dlq-messages
      AlarmDescription: Messages in SQS dead-letter queue
      Namespace: AWS/SQS
      MetricName: ApproximateNumberOfMessagesVisible
      Dimensions:
        - Name: QueueName
          Value: aplika-messages-dlq
      Statistic: Sum
      Period: 300
      EvaluationPeriods: 1
      Threshold: 0
      ComparisonOperator: GreaterThanThreshold
      AlarmActions:
        - !Ref AlarmTopic
```

---

## AWS Budgets Alert

Create a budget alert (also earns $20 in credits — Side-Quest #5):

```bash
aws budgets create-budget \
  --account-id $(aws sts get-caller-identity --query Account --output text) \
  --budget '{
    "BudgetName": "aplika-monthly",
    "BudgetLimit": {"Amount": "50", "Unit": "USD"},
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

---

## Sleep/Wake Impact on Observability

> **Stack assignment:** CloudWatch Alarms and the Dashboard are in the `aplika-runtime` stack (Chapter 13). They're deleted on `make sleep` and recreated on `make wake`. Budgets alerts are standalone (not in any stack) — they persist.

### What Survives Sleep

| Resource | Survives? | Notes |
|----------|-----------|-------|
| CloudWatch Log Groups | ✅ Yes | In persistent stack with `DeletionPolicy: Retain` |
| Budgets Alert | ✅ Yes | Standalone — not in any CFN stack |
| CloudWatch Alarms | ❌ No | Die with runtime stack; recreated on wake |
| CloudWatch Dashboard | ❌ No | Dies with runtime stack; recreated on wake |

### Persistent Cost Watch (Recommended)

Since runtime alarms die with the stack, set up a **persistent cost anomaly alarm** that catches forgotten running stacks. This is separate from the Budgets alert (Chapter 02) and uses CloudWatch billing metrics:

```bash
# Create a billing alarm that fires if estimated charges > $15/mo
# This catches forgotten running stacks while sleeping
aws cloudwatch put-metric-alarm \
  --alarm-name aplika-cost-anomaly \
  --alarm-description "Unexpected AWS charges — did you forget to make sleep?" \
  --namespace AWS/Billing \
  --metric-name EstimatedCharges \
  --dimensions Name=Currency,Value=USD \
  --statistic Maximum \
  --period 21600 \
  --evaluation-periods 1 \
  --threshold 15 \
  --comparison-operator GreaterThanThreshold \
  --alarm-actions arn:aws:sns:us-east-1:ACCOUNT_ID:aplika-alarms
```

> **Note:** Billing metrics are only available in `us-east-1`. The alarm must be created in that region. This alarm is standalone (not in any stack) — it persists across sleep/wake.

---

## CloudWatch Dashboard

Create a dashboard to visualize all key metrics:

```bash
aws cloudwatch put-dashboard \
  --dashboard-name aplika \
  --dashboard-body '{
    "widgets": [
      {
        "type": "metric",
        "properties": {
          "title": "ECS Running Tasks",
          "metrics": [
            ["AWS/ECS", "RunningTaskCount", "ClusterName", "aplika-cluster", "ServiceName", "aplika-api"],
            ["AWS/ECS", "RunningTaskCount", "ClusterName", "aplika-cluster", "ServiceName", "aplika-worker"],
            ["AWS/ECS", "RunningTaskCount", "ClusterName", "aplika-cluster", "ServiceName", "aplika-frontend"]
          ],
          "period": 60,
          "stat": "Average"
        }
      },
      {
        "type": "metric",
        "properties": {
          "title": "ALB Request Count",
          "metrics": [
            ["AWS/ApplicationELB", "RequestCount", "LoadBalancer", "aplika-alb"]
          ],
          "period": 300,
          "stat": "Sum"
        }
      },
      {
        "type": "metric",
        "properties": {
          "title": "RDS CPU Utilization",
          "metrics": [
            ["AWS/RDS", "CPUUtilization", "DBInstanceIdentifier", "aplika-db"]
          ],
          "period": 300,
          "stat": "Average"
        }
      }
    ]
  }'
```

---

## X-Ray (Optional)

AWS X-Ray provides distributed tracing — see how a request flows through your services.

**Free tier:** 100,000 traces/month (Always Free)

To enable X-Ray on ECS, add a sidecar container:
```yaml
# Add to task definition containerDefinitions
- Name: xray-daemon
  Image: public.ecr.aws/xray/aws-xray-daemon:latest
  PortMappings:
    - ContainerPort: 2000
      Protocol: udp
  Essential: false
```

> **Exam note:** X-Ray uses segments and subsegments to trace requests across services. The X-Ray SDK instruments your application code to send trace data to the X-Ray daemon.

---

## Developer Associate Exam Mapping

| Concept | Why It's on the Exam |
|---------|---------------------|
| CloudWatch Logs | Centralized logging |
| Log retention | Cost management |
| CloudWatch Alarms | Automated alerting |
| SNS notifications | Alarm delivery |
| CloudWatch Dashboards | Visualization |
| Budgets | Cost monitoring |
| X-Ray | Distributed tracing |

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| No logs appearing | Log group doesn't exist | Create it; check ECS log configuration |
| Alarm always in INSUFFICIENT_DATA | No metrics | Check namespace, dimensions, and metric name |
| Budget alert not sending | SNS subscription not confirmed | Check email for confirmation link |

## Next Step

[Chapter 12: Migrations & Releases](12-migrations-and-releases.md) — manage database migrations and release sequences.
