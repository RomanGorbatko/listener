<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241003093136 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE position ALTER stop_loss_price DROP NOT NULL');
        $this->addSql('ALTER TABLE position ALTER take_profit_price DROP NOT NULL');
        $this->addSql('ALTER TABLE position ALTER pnl DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE position ALTER stop_loss_price SET NOT NULL');
        $this->addSql('ALTER TABLE position ALTER take_profit_price SET NOT NULL');
        $this->addSql('ALTER TABLE position ALTER pnl SET NOT NULL');
    }
}
