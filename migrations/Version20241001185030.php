<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241001185030 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE confirmation (id UUID NOT NULL, intent_id UUID NOT NULL, notified_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, original_message TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_483D123CC8E919CC ON confirmation (intent_id)');
        $this->addSql('COMMENT ON COLUMN confirmation.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN confirmation.intent_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN confirmation.notified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN confirmation.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE confirmation ADD CONSTRAINT FK_483D123CC8E919CC FOREIGN KEY (intent_id) REFERENCES intent (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE confirmation DROP CONSTRAINT FK_483D123CC8E919CC');
        $this->addSql('DROP TABLE confirmation');
    }
}
