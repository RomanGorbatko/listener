<?php

namespace App\Command;

use App\Trader\TradeManager;
use ccxt\pro\binance;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function React\Async\{async, await};

#[AsCommand(
    name: 'app:test-ccxt',
)]
class TestCcxtCommand extends Command
{
    public function __construct(
        private readonly TradeManager $tradeManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loop = Loop::get();
        if ($loop instanceof LoopInterface) {
            $callback = async(fn() => await($this->tradeManager->listenOpenPositions()));

            $loop->addPeriodicTimer(0.1, $callback);
            $loop->run();
        }

        return Command::SUCCESS;
    }
}
