# Chapter 07: SQS & Worker

> Create an SQS queue with a dead-letter queue, configure the Messenger transport, and understand SQS concepts for the exam.

**Estimated duration:** 1-2 hours | **Cost:** ~$0 (Always Free: 1M requests/month)

---

## What is SQS?

**Amazon SQS (Simple Queue Service)** is a fully managed message queue. It decouples producers (your API) from consumers (your worker). Messages wait in the queue until a consumer processes them.

> **Exam note:** SQS is heavily tested. Know: standard vs FIFO queues, visibility timeout, dead-letter queues, long polling, and message retention.

### SQS Core Concepts

| Concept | What It Is | Why It Matters |
|---------|-----------|----------------|
| **Queue** | A buffer that stores messages | Decouples producers from consumers |
| **Message** | Up to 256 KB of data | Contains the serialized Symfony Messenger envelope |
| **Visibility timeout** | Time a message is hidden after being read | Prevents duplicate processing |
| **Dead-letter queue (DLQ)** | Queue for messages that fail N times | Prevents poison messages from blocking the queue |
| **Long polling** | Wait up to 20s for messages | Reduces empty responses and costs |
| **Message retention** | How long unprocessed messages live | Default 4 days; max 14 days |

---

## CloudFormation Template

```yaml
# sqs.yaml
AWSTemplateFormatVersion: '2010-09-09'
Description: Aplika SQS queues (main + dead-letter)

Parameters:
  EnvironmentName:
    Type: String
    Default: aplika

Resources:
  # ============================================================
  # Dead-Letter Queue — receives messages that fail processing
  # ============================================================
  DeadLetterQueue:
    Type: AWS::SQS::Queue
    Properties:
      QueueName: !Sub '${EnvironmentName}-messages-dlq'
      MessageRetentionPeriod: 1209600   # 14 days (max)
      Tags:
        - Key: Environment
          Value: !Ref EnvironmentName

  # ============================================================
  # Main Queue — async messages from the API
  # ============================================================
  MainQueue:
    Type: AWS::SQS::Queue
    Properties:
      QueueName: !Sub '${EnvironmentName}-messages'
      VisibilityTimeout: 65            # 65 seconds (must be > worker processing time)
      MessageRetentionPeriod: 345600   # 4 days
      ReceiveMessageWaitTimeSeconds: 20  # Long polling
      RedrivePolicy:
        deadLetterTargetArn: !GetAtt DeadLetterQueue.Arn
        maxReceiveCount: 3             # Move to DLQ after 3 failed attempts
      Tags:
        - Key: Environment
          Value: !Ref EnvironmentName

  # ============================================================
  # Allow the ECS task role to use the queues
  # ============================================================
  QueuePolicy:
    Type: AWS::SQS::QueuePolicy
    Properties:
      Queues:
        - !Ref MainQueue
        - !Ref DeadLetterQueue
      PolicyDocument:
        Statement:
          - Effect: Allow
            Principal:
              AWS: !Sub 'arn:aws:iam::${AWS::AccountId}:role/aplika-task-role'
            Action:
              - sqs:SendMessage
              - sqs:ReceiveMessage
              - sqs:DeleteMessage
              - sqs:GetQueueAttributes
              - sqs:GetQueueUrl
            Resource:
              - !GetAtt MainQueue.Arn
              - !GetAtt DeadLetterQueue.Arn

Outputs:
  MainQueueURL:
    Value: !Ref MainQueue
  MainQueueArn:
    Value: !GetAtt MainQueue.Arn
  DeadLetterQueueURL:
    Value: !Ref DeadLetterQueue
  DeadLetterQueueArn:
    Value: !GetAtt DeadLetterQueue.Arn
```

---

## Visibility Timeout Deep Dive

The **visibility timeout** is critical to understand:

```
1. Worker reads message from queue
2. Message becomes "invisible" for VisibilityTimeout seconds
3. Worker processes the message
4. Worker deletes the message from queue
5. If worker crashes before deleting → message reappears after timeout
```

**Our settings:**
- `VisibilityTimeout: 65` seconds
- Worker `--time-limit=3600` (1 hour)

The visibility timeout (65s) must be **longer than the expected processing time** but **shorter than the time limit**. If processing a message takes 30 seconds, 65 seconds is safe. If it takes 120 seconds, the message will reappear and be processed again (duplicate).

> **Exam tip:** Visibility timeout default is 30 seconds. For long-running tasks, increase it. The maximum is 12 hours.

### Relationship to `--time-limit`

| Setting | Value | Purpose |
|---------|-------|---------|
| Visibility timeout | 65s | How long a message is hidden after being read |
| `--time-limit` | 3600s | How long the worker runs before restarting |
| `--memory-limit` | 128M | Restart worker if memory exceeds this |

The worker restarts every hour (or at 128 MB memory). This is a safety valve — prevents memory leaks and ensures the worker picks up new environment variables after deployments.

---

## Dead-Letter Queue (DLQ)

When a message fails processing 3 times (`maxReceiveCount: 3`), SQS moves it to the DLQ.

**Why DLQ matters:**
- Prevents "poison messages" from blocking the queue
- Allows you to inspect failed messages later
- The main queue keeps processing other messages

**Monitoring the DLQ:**
```bash
# Check DLQ message count
aws sqs get-queue-attributes \
  --queue-url $(aws sqs get-queue-url --queue-name aplika-messages-dlq --output text) \
  --attribute-names ApproximateNumberOfMessages \
  --query 'Attributes.ApproximateNumberOfMessages' --output text
```

If DLQ message count > 0, investigate! Check CloudWatch logs for the worker to understand why messages are failing.

---

## Deploy

> **Stack assignment:** SQS queues go into the `aplika-persistent` stack (Chapter 13). They're Always Free and persist across sleep/wake cycles.

```bash
aws cloudformation deploy \
  --template-file sqs.yaml \
  --stack-name aplika-sqs \
  --region us-east-1
```

### Get the Queue URL

```bash
QUEUE_URL=$(aws sqs get-queue-url --queue-name aplika-messages --output text)
echo "Queue URL: $QUEUE_URL"
```

---

## Test the Queue

```bash
# Send a test message
aws sqs send-message \
  --queue-url $QUEUE_URL \
  --message-body '{"test": "hello from SQS"}'

# Receive the message (long polling waits up to 20s)
aws sqs receive-message \
  --queue-url $QUEUE_URL \
  --wait-time-seconds 20

# Delete the message (use ReceiptHandle from receive output)
aws sqs delete-message \
  --queue-url $QUEUE_URL \
  --receipt-handle <ReceiptHandle>
```

---

## SQS Pricing

| Aspect | Free Tier | Paid |
|--------|-----------|------|
| Standard requests | 1M/month (Always Free) | $0.40 per million |
| FIFO requests | 1M/month (Always Free) | $0.50 per million |
| Data transfer | 1 GB/month free | $0.09/GB |

For Aplika's usage (~10K messages/month), SQS is effectively free.

---

## Standard vs FIFO Queues

| Feature | Standard | FIFO |
|---------|----------|------|
| Ordering | Best-effort | Guaranteed |
| Delivery | At-least-once (duplicates possible) | Exactly-once |
| Throughput | Unlimited | 3,000 messages/sec (with batching) |
| Use case | Most workloads | Ordered processing (e.g., financial) |
| Price | $0.40/million | $0.50/million |

**We use Standard** because our messages (AnalyzeJobDescription, SendEmail) don't require strict ordering.

---

## Developer Associate Exam Mapping

| Concept | Why It's on the Exam |
|---------|---------------------|
| SQS queue types | Standard vs FIFO tradeoffs |
| Visibility timeout | Message processing reliability |
| Dead-letter queue | Error handling |
| Long polling | Cost optimization |
| Message retention | Data lifecycle |
| Redrive policy | Automatic error handling |
| Queue policies | IAM + resource policies |

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| Messages processed twice | Visibility timeout too short | Increase visibility timeout |
| Messages stuck in queue | Worker not running or wrong queue URL | Check ECS service status; verify `MESSENGER_TRANSPORT_DSN` |
| Messages in DLQ | Processing failures | Check worker logs; fix the bug; redrive messages |
| `AccessDenied` on send/receive | Missing IAM permissions | Check task role has SQS permissions |

## Next Step

[Chapter 08: Route 53 & TLS](08-route53-and-tls.md) — configure DNS and HTTPS.
