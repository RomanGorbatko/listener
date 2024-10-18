<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241017201834 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account DROP currency');
        $this->addSql('ALTER TABLE account ALTER amount TYPE BIGINT USING amount::bigint');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account ADD currency VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE account ALTER amount TYPE VARCHAR(255)');
    }
}
