<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241018143441 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intent DROP currency');
        $this->addSql('ALTER TABLE intent ALTER amount TYPE BIGINT USING amount::bigint');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intent ADD currency VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE intent ALTER amount TYPE VARCHAR(255)');
    }
}
