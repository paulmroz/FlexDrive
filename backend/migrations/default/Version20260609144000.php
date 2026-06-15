<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20260609144000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create audit_logs table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(sql: 'CREATE TABLE audit_logs (
            id VARCHAR(36) NOT NULL,
            event_type VARCHAR(100) NOT NULL,
            aggregate_id VARCHAR(36) NOT NULL,
            aggregate_type VARCHAR(50) NOT NULL,
            payload JSON NOT NULL,
            user_id VARCHAR(36) DEFAULT NULL,
            occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql(sql: 'CREATE INDEX idx_audit_logs_aggregate ON audit_logs (aggregate_type, aggregate_id)');
        $this->addSql(sql: 'CREATE INDEX idx_audit_logs_user_id ON audit_logs (user_id)');
        $this->addSql(sql: 'CREATE INDEX idx_audit_logs_occurred_at ON audit_logs (occurred_at DESC)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(sql: 'DROP TABLE audit_logs');
    }
}
