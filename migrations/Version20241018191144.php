<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241018191144 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN account.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE confirmation ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN confirmation.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE intent ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN intent.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE position ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN position.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE ticker ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN ticker.updated_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ticker DROP updated_at');
        $this->addSql('ALTER TABLE confirmation DROP updated_at');
        $this->addSql('ALTER TABLE position DROP updated_at');
        $this->addSql('ALTER TABLE intent DROP updated_at');
        $this->addSql('ALTER TABLE account DROP updated_at');
    }
}
