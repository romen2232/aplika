<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916060000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create refresh_tokens table for token rotation and revocation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE refresh_tokens (
            id UUID NOT NULL,
            user_id UUID NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            family_id UUID NOT NULL,
            expires_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
            revoked_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_refresh_tokens_token_hash ON refresh_tokens (token_hash)');
        $this->addSql('CREATE INDEX idx_refresh_tokens_family_id ON refresh_tokens (family_id)');
        $this->addSql('COMMENT ON COLUMN refresh_tokens.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN refresh_tokens.revoked_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN refresh_tokens.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE refresh_tokens ADD CONSTRAINT fk_refresh_tokens_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE refresh_tokens DROP CONSTRAINT fk_refresh_tokens_user_id');
        $this->addSql('DROP TABLE refresh_tokens');
    }
}
