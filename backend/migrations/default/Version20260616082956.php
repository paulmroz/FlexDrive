<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260616082956 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_audit_logs_aggregate');
        $this->addSql('DROP INDEX idx_audit_logs_occurred_at');
        $this->addSql('DROP INDEX idx_audit_logs_user_id');
        $this->addSql('ALTER TABLE audit_logs ALTER occurred_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN audit_logs.occurred_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE cars ADD locked_by_saga VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE cars ADD lock_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN cars.lock_expires_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "audit_logs" ALTER occurred_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN "audit_logs".occurred_at IS NULL');
        $this->addSql('CREATE INDEX idx_audit_logs_aggregate ON "audit_logs" (aggregate_type, aggregate_id)');
        $this->addSql('CREATE INDEX idx_audit_logs_occurred_at ON "audit_logs" (occurred_at)');
        $this->addSql('CREATE INDEX idx_audit_logs_user_id ON "audit_logs" (user_id)');
        $this->addSql('ALTER TABLE "cars" DROP locked_by_saga');
        $this->addSql('ALTER TABLE "cars" DROP lock_expires_at');
    }
}
