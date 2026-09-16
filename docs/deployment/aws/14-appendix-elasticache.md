# Appendix A: ElastiCache (Redis)

> When to add Redis back, how to configure it, and what code changes are needed.

---

## When to Add ElastiCache

Add ElastiCache when you need any of these:

| Need | Why ElastiCache Helps |
|------|----------------------|
| **Shared cache across tasks** | Filesystem cache is per-task; ElastiCache is shared |
| **Shared rate limiting** | Filesystem rate limiter is per-task; ElastiCache is centralized |
| **Session storage** | If you add server-side sessions |
| **Real-time features** | Pub/sub, WebSocket presence |

### Cost

| Instance | vCPU | Memory | Monthly Cost |
|----------|------|--------|-------------|
| cache.t4g.micro | 2 | 0.5 GiB | ~$12.20 |
| cache.t4g.small | 2 | 1.37 GiB | ~$24.50 |

> **Exam note:** ElastiCache is a managed Redis (or Memcached) service. Redis supports persistence, replication, and pub/sub. Memcached is simpler but doesn't support persistence.

---

## Architecture Change

```
Current:  ECS Task → Filesystem cache (per-task)
With EC:  ECS Task → ElastiCache Redis (shared)
```

---

## Code Changes

### 1. Add ElastiCache to VPC

```yaml
# Add to vpc.yaml
ElastiCacheSubnetGroup:
  Type: AWS::ElastiCache::SubnetGroup
  Properties:
    Description: Aplika cache subnets
    SubnetIds:
      - !ImportValue aplika-vpc-PublicSubnetAId
      - !ImportValue aplika-vpc-PublicSubnetBId

ElastiCacheSecurityGroup:
  Type: AWS::EC2::SecurityGroup
  Properties:
    GroupDescription: ElastiCache - allow Redis from ECS tasks
    VpcId: !ImportValue aplika-vpc-VPCId
    SecurityGroupIngress:
      - IpProtocol: tcp
        FromPort: 6379
        ToPort: 6379
        SourceSecurityGroupId: !ImportValue aplika-vpc-TaskSecurityGroupId

RedisCluster:
  Type: AWS::ElastiCache::CacheCluster
  Properties:
    CacheNodeType: cache.t4g.micro
    Engine: redis
    NumCacheNodes: 1
    CacheSubnetGroupName: !Ref ElastiCacheSubnetGroup
    VpcSecurityGroupIds:
      - !Ref ElastiCacheSecurityGroup
```

### 2. Update Cache Configuration

**`api/config/packages/cache.yaml`:**
```yaml
framework:
    cache:
        app: cache.adapter.redis
        default_redis_provider: '%env(REDIS_URL)%'
        pools:
            cache.redis:
                adapter: cache.adapter.redis
```

### 3. Update Rate Limiter

**`api/config/packages/rate_limiter.yaml`:**
```yaml
framework:
    rate_limiter:
        api_public:
            policy: 'sliding_window'
            limit: 15
            interval: '1 minute'
            cache_pool: 'cache.redis'
        api_authenticated:
            policy: 'sliding_window'
            limit: 50
            interval: '1 minute'
            cache_pool: 'cache.redis'
```

### 4. Add REDIS_URL to ECS Task Definition

```yaml
Environment:
  - Name: REDIS_URL
    Value: !Sub 'redis://${RedisCluster.RedisEndpoint.Address}:6379'
```

### 5. Re-add predis/predis

```bash
composer require predis/predis
```

---

## ElastiCache vs Self-Managed Redis on EC2

| Aspect | ElastiCache | EC2 Redis |
|--------|------------|-----------|
| Management | AWS manages patching, failover | You manage everything |
| Cost | ~$12/mo (t4g.micro) | ~$4/mo (t3.micro) + your time |
| Multi-AZ | Automatic failover | Manual setup |
| Backups | Automated | Manual |
| Scaling | Console/API | Manual |

For learning, ElastiCache is worth the cost for the operational simplicity.

---

## Developer Associate Exam Mapping

| Concept | Why It's on the Exam |
|---------|---------------------|
| ElastiCache Redis vs Memcached | Caching strategy |
| Cache subnet groups | Network configuration |
| Security groups for cache | Network security |
| Cache eviction policies | Memory management |
| Multi-AZ replication | High availability |

## Next Step

[Appendix B: Exam Map](15-appendix-exam-map.md) — full Developer Associate topic matrix.
