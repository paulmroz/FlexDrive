<?php

declare(strict_types=1);

namespace SubscriptionMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260602075646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE "payments" (id VARCHAR(36) NOT NULL, session_id VARCHAR(255) NOT NULL, subscription_id VARCHAR(36) NOT NULL, amount INT NOT NULL, currency VARCHAR(3) NOT NULL, status VARCHAR(30) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_65D29B32613FECDF ON "payments" (session_id)');
        $this->addSql('COMMENT ON COLUMN "payments".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "payments".paid_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE "processed_webhooks" (id VARCHAR(36) NOT NULL, event_id VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E4DE2FF71F7E88B ON "processed_webhooks" (event_id)');
        $this->addSql('COMMENT ON COLUMN "processed_webhooks".created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE "payments"');
        $this->addSql('DROP TABLE "processed_webhooks"');
    }
}
