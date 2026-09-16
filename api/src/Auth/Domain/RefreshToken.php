<?php

declare(strict_types=1);

namespace App\Auth\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

final class RefreshToken
{
    private ?DateTimeImmutable $revokedAt = null;

    private function __construct(
        private readonly string $id,
        private readonly string $userId,
        private readonly string $tokenHash,
        private readonly string $familyId,
        private readonly DateTimeImmutable $expiresAt,
        private readonly DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(
        string $id,
        string $userId,
        string $tokenHash,
        string $familyId,
        DateTimeImmutable $expiresAt,
    ): self {
        if (empty($id)) {
            throw new InvalidArgumentException('RefreshToken ID cannot be empty');
        }

        if (empty($userId)) {
            throw new InvalidArgumentException('User ID cannot be empty');
        }

        if (empty($tokenHash)) {
            throw new InvalidArgumentException('Token hash cannot be empty');
        }

        if (empty($familyId)) {
            throw new InvalidArgumentException('Family ID cannot be empty');
        }

        return new self($id, $userId, $tokenHash, $familyId, $expiresAt, new DateTimeImmutable());
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }

    public function isRevoked(): bool
    {
        return null !== $this->revokedAt;
    }

    public function revoke(): void
    {
        $this->revokedAt = new DateTimeImmutable();
    }

    public function tokenHash(): string
    {
        return $this->tokenHash;
    }

    public function familyId(): string
    {
        return $this->familyId;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }
}
