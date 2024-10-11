<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241003085452 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE position (id UUID NOT NULL, intent_id UUID NOT NULL, account_id UUID NOT NULL, status VARCHAR(255) NOT NULL, open_price DOUBLE PRECISION NOT NULL, stop_loss_price DOUBLE PRECISION NOT NULL, take_profit_price DOUBLE PRECISION NOT NULL, pnl DOUBLE PRECISION NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_462CE4F5C8E919CC ON position (intent_id)');
        $this->addSql('CREATE INDEX IDX_462CE4F59B6B5FBA ON position (account_id)');
        $this->addSql('COMMENT ON COLUMN position.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN position.intent_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN position.account_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN position.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F5C8E919CC FOREIGN KEY (intent_id) REFERENCES intent (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F59B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE position DROP CONSTRAINT FK_462CE4F5C8E919CC');
        $this->addSql('ALTER TABLE position DROP CONSTRAINT FK_462CE4F59B6B5FBA');
        $this->addSql('DROP TABLE position');
    }
}
