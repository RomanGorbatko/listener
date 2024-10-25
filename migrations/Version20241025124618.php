<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241025124618 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intent ADD confirmation_trades_cost_buy DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE intent ADD confirmation_trades_cost_sell DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE intent DROP trades_cost_buy');
        $this->addSql('ALTER TABLE intent DROP trades_cost_sell');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intent ADD trades_cost_buy DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE intent ADD trades_cost_sell DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE intent DROP confirmation_trades_cost_buy');
        $this->addSql('ALTER TABLE intent DROP confirmation_trades_cost_sell');
    }
}
