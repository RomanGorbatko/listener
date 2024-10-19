<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241019185005 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE position ADD take_profit_trailed INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE position ADD stop_loss_trailed INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE position DROP take_profit_trailed');
        $this->addSql('ALTER TABLE position DROP stop_loss_trailed');
    }
}
