<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241014175400 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE position ALTER pnl TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE position ALTER amount TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE position ALTER amount SET NOT NULL');
        $this->addSql('ALTER TABLE position RENAME COLUMN open_price TO entry_price');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE position ALTER pnl TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE position ALTER amount TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE position ALTER amount DROP NOT NULL');
        $this->addSql('ALTER TABLE position RENAME COLUMN entry_price TO open_price');
    }
}
