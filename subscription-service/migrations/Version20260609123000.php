<?php

declare(strict_types=1);

namespace SubscriptionMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20260609123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add performance indexes to subscriptions and payments tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(sql: 'CREATE INDEX idx_subscriptions_user_id ON subscriptions (user_id)');
        $this->addSql(sql: 'CREATE INDEX idx_subscriptions_car_id ON subscriptions (car_id)');
        $this->addSql(sql: 'CREATE INDEX idx_subscriptions_status ON subscriptions (status)');
        $this->addSql(sql: 'CREATE INDEX idx_payments_session_id ON payments (session_id)');
        $this->addSql(sql: 'CREATE INDEX idx_payments_subscription_id ON payments (subscription_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(sql: 'DROP INDEX idx_subscriptions_user_id');
        $this->addSql(sql: 'DROP INDEX idx_subscriptions_car_id');
        $this->addSql(sql: 'DROP INDEX idx_subscriptions_status');
        $this->addSql(sql: 'DROP INDEX idx_payments_session_id');
        $this->addSql(sql: 'DROP INDEX idx_payments_subscription_id');
    }
}
