<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241003093800 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE position ADD risk DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE position ADD leverage INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE position DROP risk');
        $this->addSql('ALTER TABLE position DROP leverage');
    }
}
