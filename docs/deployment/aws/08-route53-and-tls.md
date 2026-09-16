# Chapter 08: Route 53 & TLS

> Configure DNS with Route 53, issue a TLS certificate with ACM, and set up HTTPS on the ALB.

**Estimated duration:** 1-2 hours | **Cost:** ~$22.50/month (ALB + Route 53)

---

## What is Route 53?

**Amazon Route 53** is a DNS web service. It translates domain names (like `aplika.work`) into IP addresses (or AWS resource aliases).

> **Exam note:** Route 53 is tested frequently. Know: hosted zones, record types (A, AAAA, CNAME, alias), routing policies (simple, weighted, latency, failover), and health checks.

---

## Create a Hosted Zone

> **Stack assignment:** The Route 53 hosted zone and ACM certificate go into the `aplika-persistent` stack (Chapter 13). The alias records (pointing to the ALB) go into the `aplika-runtime` stack — because the ALB's DNS name changes on every wake, alias records must be recreated.

A **hosted zone** is a container for DNS records for a domain.

```bash
aws route53 create-hosted-zone \
  --name aplika.work \
  --caller-reference $(date +%s) \
  --hosted-zone-config Comment="Aplika production DNS"
```

Get the nameservers:
```bash
aws route53 get-hosted-zone \
  --id $(aws route53 list-hosted-zones-by-name --dns-name aplika.work \
    --query 'HostedZones[0].Id' --output text | sed 's/\/hostedzone\///') \
  --query 'DelegationSet.NameServers' --output table
```

### NS Delegation at Your Registrar

You purchased `aplika.work` from a registrar (e.g., Namecheap, GoDaddy, Google Domains). You need to tell your registrar to use Route 53's nameservers:

1. Log into your registrar's dashboard
2. Find DNS/Nameserver settings for `aplika.work`
3. Replace the default nameservers with the 4 Route 53 nameservers from above
4. Save changes

> **ICANN Warning:** When you change nameservers for a newly registered domain, ICANN may send a verification email to your registrant email. **Click the verification link within 15 days** or your domain will be suspended.

### DNS Propagation

Nameserver changes take 24-48 hours to propagate globally. You can verify progress:
```bash
dig NS aplika.work +short
# Should eventually show Route 53 nameservers
```

---

## ACM Certificate

**AWS Certificate Manager (ACM)** provides free public TLS certificates. These certificates encrypt HTTPS traffic between clients and the ALB.

> **Exam note:** ACM certificates are **free** for public certificates used with AWS services. You cannot use an ACM certificate on a plain EC2 instance or nginx server — ACM manages the private key and only shares it with integrated services (ALB, CloudFront, API Gateway). This is a common exam question.

### Request a Certificate

```bash
# Request a certificate for both domains
CERT_ARN=$(aws acm request-certificate \
  --domain-name "*.aplika.work" \
  --subject-alternative-names "aplika.work" \
  --validation-method DNS \
  --region us-east-1 \
  --query 'CertificateArn' --output text)

echo "Certificate ARN: $CERT_ARN"
```

### DNS Validation

ACM needs to verify you own the domain. It creates a CNAME record in Route 53:

```bash
# Get the DNS validation records
aws acm describe-certificate \
  --certificate-arn $CERT_ARN \
  --region us-east-1 \
  --query 'Certificate.DomainValidationOptions[0].ResourceRecord' --output json
```

This returns a CNAME record (name and value). Add it to Route 53:

```bash
# Create the validation CNAME record
aws route53 change-resource-record-sets \
  --hosted-zone-id $(aws route53 list-hosted-zones-by-name --dns-name aplika.work \
    --query 'HostedZones[0].Id' --output text | sed 's/\/hostedzone\///') \
  --change-batch '{
    "Changes": [{
      "Action": "UPSERT",
      "ResourceRecordSet": {
        "Name": "<CNAME_NAME_FROM_ACM>",
        "Type": "CNAME",
        "TTL": 300,
        "ResourceRecords": [{"Value": "<CNAME_VALUE_FROM_ACM>"}]
      }
    }]
  }'
```

Wait for validation:
```bash
aws acm wait certificate-validated \
  --certificate-arn $CERT_ARN \
  --region us-east-1
```

This can take 5-30 minutes.

---

## Update ALB for HTTPS

Now update the ALB to use the ACM certificate:

```yaml
# Add to ecs.yaml or create a separate template

  # HTTPS Listener with ACM certificate
  HTTPSListener:
    Type: AWS::ElasticLoadBalancingV2::Listener
    Properties:
      LoadBalancerArn: !Ref ALB
      Port: 443
      Protocol: HTTPS
      Certificates:
        - CertificateArn: !Ref HTTPSCertificateArn
      SslPolicy: ELBSecurityPolicy-TLS13-1-2-2021-06
      DefaultAction:
        - Type: forward
          TargetGroupArn: !Ref FrontendTargetGroup

  # HTTPS API routing rule
  HTTPSAPIListenerRule:
    Type: AWS::ElasticLoadBalancingV2::ListenerRule
    Properties:
      ListenerArn: !Ref HTTPSListener
      Priority: 1
      Conditions:
        - Field: host-header
          Values:
            - api.aplika.work
      Actions:
        - Type: forward
          TargetGroupArn: !Ref APITargetGroup

  # Redirect HTTP to HTTPS
  HTTPListener:
    Type: AWS::ElasticLoadBalancingV2::Listener
    Properties:
      LoadBalancerArn: !Ref ALB
      Port: 80
      Protocol: HTTP
      DefaultAction:
        - Type: redirect
          RedirectConfig:
            Protocol: HTTPS
            Port: '443'
            StatusCode: HTTP_301
```

> **Exam note:** Always redirect HTTP to HTTPS. Use `ELBSecurityPolicy-TLS13-1-2-2021-06` or newer for the SSL policy — it enforces TLS 1.2+ and disables weak ciphers.

---

## Create DNS Records

Point `aplika.work` and `api.aplika.work` to the ALB:

```bash
HOSTED_ZONE_ID=$(aws route53 list-hosted-zones-by-name --dns-name aplika.work \
  --query 'HostedZones[0].Id' --output text | sed 's/\/hostedzone\///')

ALB_DNS=$(aws cloudformation describe-stacks --stack-name aplika-ecs \
  --query 'Stacks[0].Outputs[?OutputKey==`ALBDNSName`].OutputValue' --output text)

ALB_HOSTED_ZONE_ID=$(aws elbv2 describe-load-balancers \
  --names aplika-alb \
  --query 'LoadBalancers[0].CanonicalHostedZoneId' --output text)

# Create alias records (A records pointing to ALB)
aws route53 change-resource-record-sets \
  --hosted-zone-id $HOSTED_ZONE_ID \
  --change-batch "{
    \"Changes\": [
      {
        \"Action\": \"UPSERT\",
        \"ResourceRecordSet\": {
          \"Name\": \"aplika.work\",
          \"Type\": \"A\",
          \"AliasTarget\": {
            \"HostedZoneId\": \"$ALB_HOSTED_ZONE_ID\",
            \"DNSName\": \"dualstack.$ALB_DNS\",
            \"EvaluateTargetHealth\": true
          }
        }
      },
      {
        \"Action\": \"UPSERT\",
        \"ResourceRecordSet\": {
          \"Name\": \"api.aplika.work\",
          \"Type\": \"A\",
          \"AliasTarget\": {
            \"HostedZoneId\": \"$ALB_HOSTED_ZONE_ID\",
            \"DNSName\": \"dualstack.$ALB_DNS\",
            \"EvaluateTargetHealth\": true
          }
        }
      }
    ]
  }"
```

> **Exam note:** Route 53 **alias records** are like CNAME records but they resolve to AWS resources directly (ALB, CloudFront, S3). They're free (no charge for alias queries to AWS resources) and faster than CNAME lookups.

---

## Final Environment Variable Values

After this chapter, all URLs are finalized:

| Variable | Value |
|----------|-------|
| `DEFAULT_URI` | `https://api.aplika.work` |
| `CORS_ALLOW_ORIGIN` | `^https://(app\.aplika\.work\|api\.aplika\.work)$` |
| `NEXT_PUBLIC_API_URL` | `https://api.aplika.work` |
| `MAILER_DSN` | `ses+api://default?region=us-east-1` |

---

## Verify

```bash
# Check DNS resolution
dig A aplika.work +short
dig A api.aplika.work +short

# Test HTTPS
curl -I https://aplika.work
curl -I https://api.aplika.work/health

# Check certificate
aws acm list-certificates --region us-east-1 \
  --query 'CertificateSummaryList[].[DomainName,Status]' --output table
```

---

## Why ACM Certificates Can't Be Used on Plain EC2

ACM manages the certificate's private key. It never exports the key to you. Instead, it provisions the certificate directly to integrated AWS services:

- ✅ ALB, CloudFront, API Gateway
- ❌ EC2, nginx, Apache, any non-AWS service

If you need a certificate on a plain server, use **Let's Encrypt** (free, but you manage renewal) or buy one from a CA.

---

## Developer Associate Exam Mapping

| Concept | Why It's on the Exam |
|---------|---------------------|
| Route 53 hosted zones | DNS management |
| Record types (A, CNAME, alias) | DNS resolution |
| Alias vs CNAME | Cost and performance |
| ACM certificate validation | Domain ownership |
| ACM integration limits | Where certs can be used |
| HTTPS on ALB | Security |
| HTTP to HTTPS redirect | Best practice |
| SSL policies | TLS version enforcement |

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| DNS not resolving | NS delegation not complete | Wait 24-48h; check registrar nameservers |
| ACM validation pending | CNAME not added to Route 53 | Add the CNAME record; wait 5-30 min |
| 502 Bad Gateway from ALB | Targets unhealthy | Check target group health; verify container port |
| Mixed content warnings | HTTP resources on HTTPS page | Ensure all resources use HTTPS |
| Certificate not appearing | Wrong region | ACM certificates must be in us-east-1 for ALB in us-east-1 |

## Next Step

[Chapter 09: SES Email](09-ses-email.md) — configure email sending.
