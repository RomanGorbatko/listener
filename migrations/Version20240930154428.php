<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240930154428 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE intent (id UUID NOT NULL, ticker_id UUID NOT NULL, exchange VARCHAR(255) NOT NULL, direction VARCHAR(255) NOT NULL, notified_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, original_message TEXT NOT NULL, volume BIGINT NOT NULL, amount VARCHAR(255) NOT NULL, currency VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BE6CFB11556B180E ON intent (ticker_id)');
        $this->addSql('COMMENT ON COLUMN intent.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN intent.ticker_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN intent.notified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN intent.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE ticker (id UUID NOT NULL, name VARCHAR(255) NOT NULL, exchanges JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN ticker.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN ticker.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE intent ADD CONSTRAINT FK_BE6CFB11556B180E FOREIGN KEY (ticker_id) REFERENCES ticker (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intent DROP CONSTRAINT FK_BE6CFB11556B180E');
        $this->addSql('DROP TABLE intent');
        $this->addSql('DROP TABLE ticker');
    }
}
