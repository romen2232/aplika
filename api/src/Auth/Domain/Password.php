<?php

declare(strict_types=1);

namespace App\Auth\Domain;

use App\Auth\Domain\Exception\WeakPasswordException;

final class Password
{
    public const MIN_LENGTH = 8;

    private function __construct(private readonly string $plainText)
    {
    }

    public static function fromPlainText(string $plainText): self
    {
        if (\strlen($plainText) < self::MIN_LENGTH) {
            throw WeakPasswordException::tooShort(self::MIN_LENGTH);
        }

        if (!preg_match('/[a-zA-Z]/', $plainText) || !preg_match('/[0-9]/', $plainText)) {
            throw WeakPasswordException::missingLettersAndNumbers();
        }

        return new self($plainText);
    }

    public function plainText(): string
    {
        return $this->plainText;
    }
}
