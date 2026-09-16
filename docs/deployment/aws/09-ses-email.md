# Chapter 09: SES Email

> Configure Amazon SES for sending emails — sandbox mode first, then production access.

**Estimated duration:** 30-60 minutes | **Cost:** ~$0 (sandbox is free; production is $0.10/1,000 emails)

---

## What is SES?

**Amazon SES (Simple Email Service)** is a managed email sending service. It handles SMTP and API-based email delivery with high deliverability.

> **Exam note:** SES is tested in the exam. Know: sandbox vs production, domain verification, DKIM, sending quotas, and bounce/complaint handling.

---

## Sandbox Mode

New SES accounts start in **sandbox mode**:
- Can only send to verified email addresses
- 200 emails/day maximum
- Good for testing; insufficient for production

---

## Verify the Domain

> **Stack assignment:** SES domain identity goes into the `aplika-persistent` stack (Chapter 13). Re-verifying DKIM after each wake would require DNS propagation delays — keeping it persistent avoids this.

```bash
# Verify aplika.work domain
aws sesv2 put-email-identity \
  --email-identity aplika.work \
  --region us-east-1
```

This generates DKIM records. Get them:
```bash
aws sesv2 get-email-identity \
  --email-identity aplika.work \
  --region us-east-1 \
  --query 'DkimAttributes.Tokens' --output json
```

Add the 3 CNAME records to Route 53:
```bash
# Each token becomes a CNAME record:
# Name: <token>._domainkey.aplika.work
# Value: <token>.dkim.amazonses.com

HOSTED_ZONE_ID=$(aws route53 list-hosted-zones-by-name --dns-name aplika.work \
  --query 'HostedZones[0].Id' --output text | sed 's/\/hostedzone\///')

# Add all 3 DKIM CNAME records
aws route53 change-resource-record-sets \
  --hosted-zone-id $HOSTED_ZONE_ID \
  --change-batch '{
    "Changes": [
      {
        "Action": "UPSERT",
        "ResourceRecordSet": {
          "Name": "<TOKEN1>._domainkey.aplika.work",
          "Type": "CNAME",
          "TTL": 300,
          "ResourceRecords": [{"Value": "<TOKEN1>.dkim.amazonses.com"}]
        }
      },
      {
        "Action": "UPSERT",
        "ResourceRecordSet": {
          "Name": "<TOKEN2>._domainkey.aplika.work",
          "Type": "CNAME",
          "TTL": 300,
          "ResourceRecords": [{"Value": "<TOKEN2>.dkim.amazonses.com"}]
        }
      },
      {
        "Action": "UPSERT",
        "ResourceRecordSet": {
          "Name": "<TOKEN3>._domainkey.aplika.work",
          "Type": "CNAME",
          "TTL": 300,
          "ResourceRecords": [{"Value": "<TOKEN3>.dkim.amazonses.com"}]
        }
      }
    ]
  }'
```

Wait for DKIM verification:
```bash
aws sesv2 get-email-identity \
  --email-identity aplika.work \
  --region us-east-1 \
  --query 'DkimAttributes.Status'
# Expected: SUCCESS
```

---

## SMTP vs API Transport for Symfony Mailer

Symfony Mailer supports three transports for SES:

| Transport | DSN Format | Pros | Cons |
|-----------|-----------|------|------|
| SMTP | `ses+smtp://...` | Universal; works with any SMTP client | Slower; needs SMTP credentials |
| HTTP | `ses+https://...` | Faster than SMTP | Uses AWS SDK v2 signing |
| **API** | `ses+api://default` | **Fastest; uses IAM roles** | Requires `symfony/amazon-mailer` |

**Recommendation: Use `ses+api://default`** with the ECS task role. This way:
- No SMTP credentials to manage
- Uses IAM role-based authentication (the task role already has SES permissions)
- Fastest transport

The DSN is simple:
```
MAILER_DSN=ses+api://default?region=us-east-1
```

> **Note:** The `ses+api://default` scheme tells Symfony to use the default AWS SDK credential chain (environment variables → IAM role). Since our ECS task has a task role with SES permissions, this works automatically.

---

## Install the Symfony Amazon Mailer Bridge

```bash
docker compose exec api composer require symfony/amazon-mailer
```

---

## Request Production Access

To send to unverified addresses, request production access:

1. Go to **SES** console → **Account dashboard**
2. Click **Request production access**
3. Fill in the form:
   - **Mail type:** Transactional
   - **Website URL:** `https://aplika.work`
   - **Use case:** Job application notifications and transactional emails
   - **Acknowledgment:** Check the box
4. Submit

AWS typically reviews within 24-48 hours. Until then, you're in sandbox mode.

---

## Sending Identity IAM Permissions

The ECS task role already has SES permissions (from Chapter 06):

```json
{
  "Effect": "Allow",
  "Action": [
    "ses:SendEmail",
    "ses:SendRawEmail"
  ],
  "Resource": "*"
}
```

In production, scope this to the verified identity:
```json
{
  "Effect": "Allow",
  "Action": [
    "ses:SendEmail",
    "ses:SendRawEmail"
  ],
  "Resource": "arn:aws:ses:us-east-1:ACCOUNT_ID:identity/aplika.work"
}
```

---

## Verify

```bash
# Check domain verification status
aws sesv2 get-email-identity \
  --email-identity aplika.work \
  --region us-east-1 \
  --query '[VerificationStatus,DkimAttributes.Status]' --output json

# Send a test email (to a verified address in sandbox mode)
aws ses send-email \
  --from "noreply@aplika.work" \
  --destination "ToAddresses=your-verified-email@example.com" \
  --message "Subject={Data=Test from Aplika},Body={Text={Data=Hello from SES!}}" \
  --region us-east-1
```

---

## Developer Associate Exam Mapping

| Concept | Why It's on the Exam |
|---------|---------------------|
| SES sandbox vs production | Email sending restrictions |
| Domain verification | Ownership proof |
| DKIM | Email authentication |
| Sending quotas | Rate limiting |
| Bounce/complaint handling | Sender reputation |
| SMTP vs API | Transport options |

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| Email not delivered | Sandbox mode — recipient not verified | Verify recipient email or request production access |
| DKIM not verified | CNAME records not propagated | Wait; verify Route 53 records |
| `AccessDenied` on send | Missing IAM permissions | Check task role has `ses:SendEmail` |
| Emails going to spam | Missing SPF/DKIM/DMARC | Add SPF record; DKIM should be verified |

## Next Step

[Chapter 10: CI/CD with GitHub OIDC](10-cicd-github-oidc.md) — automate deployments.
