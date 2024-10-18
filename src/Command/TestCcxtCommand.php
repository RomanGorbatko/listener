<?php

namespace App\Command;

use App\Trader\TradeManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function React\Async\await;

#[AsCommand(
    name: 'app:test-ccxt',
)]
class TestCcxtCommand extends Command
{
    public function __construct(
        private readonly TradeManager $tradeManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        await($this->tradeManager->listenOpenPositions());

        return Command::SUCCESS;
    }
}
