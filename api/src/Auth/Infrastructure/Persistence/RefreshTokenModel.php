<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Persistence;

use App\Auth\Domain\RefreshToken;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
#[ORM\Index(name: 'idx_refresh_tokens_token_hash', columns: ['token_hash'])]
#[ORM\Index(name: 'idx_refresh_tokens_family_id', columns: ['family_id'])]
class RefreshTokenModel
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'guid')]
    private string $userId;

    #[ORM\Column(name: 'token_hash', type: 'string', length: 64)]
    private string $tokenHash;

    #[ORM\Column(name: 'family_id', type: 'guid')]
    private string $familyId;

    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable')]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'revoked_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $revokedAt;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $userId,
        string $tokenHash,
        string $familyId,
        DateTimeImmutable $expiresAt,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $revokedAt = null,
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->tokenHash = $tokenHash;
        $this->familyId = $familyId;
        $this->expiresAt = $expiresAt;
        $this->createdAt = $createdAt;
        $this->revokedAt = $revokedAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getFamilyId(): string
    {
        return $this->familyId;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getRevokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function setRevokedAt(?DateTimeImmutable $revokedAt): void
    {
        $this->revokedAt = $revokedAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function toDomain(): RefreshToken
    {
        $token = RefreshToken::create(
            $this->id,
            $this->userId,
            $this->tokenHash,
            $this->familyId,
            $this->expiresAt,
        );

        if (null !== $this->revokedAt) {
            $token->revoke();
        }

        return $token;
    }

    public static function fromDomain(RefreshToken $token): self
    {
        return new self(
            $token->id(),
            $token->userId(),
            $token->tokenHash(),
            $token->familyId(),
            $token->expiresAt(),
            $token->createdAt(),
            $token->revokedAt(),
        );
    }
}
