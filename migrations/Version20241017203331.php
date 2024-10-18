<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241017203331 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE position ALTER pnl TYPE BIGINT USING pnl::bigint');
        $this->addSql('ALTER TABLE position ALTER amount TYPE BIGINT USING amount::bigint');
        $this->addSql('ALTER TABLE position ALTER commission DROP DEFAULT');
        $this->addSql('ALTER TABLE position ALTER commission TYPE BIGINT USING commission::bigint');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE position ALTER amount TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE position ALTER pnl TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE position ALTER commission TYPE VARCHAR(255)');
    }
}
