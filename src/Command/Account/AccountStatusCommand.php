<?php

namespace App\Command\Account;

use App\Entity\Account;
use App\Entity\Position;
use App\Enum\PositionStatusEnum;
use App\Event\TelegramLogEvent;
use App\Helper\MoneyHelper;
use App\Helper\TickerHelper;
use App\Repository\AccountRepository;
use App\Repository\PositionRepository;
use App\Trader\TradingSimulator;
use ccxt\binance;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'account:status',
)]
class AccountStatusCommand extends Command
{
    private InputInterface $input;
    private OutputInterface $output;
    private SymfonyStyle $io;
    private ?BufferedOutput $bufferedOutput = null;

    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly PositionRepository $positionRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('telegram', null, InputOption::VALUE_OPTIONAL, '', false);

        $this->addOption('accounts', null, InputOption::VALUE_OPTIONAL, '', false);
        $this->addOption('positions', null, InputOption::VALUE_OPTIONAL, '', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);

        $showAccountStatus = false !== $this->input->getOption('accounts');
        $showPositionsStatus = false !== $this->input->getOption('positions');

        if ($showAccountStatus) {
            $this->renderAccountsStatus();
        }

        if ($showPositionsStatus) {
            $this->renderPositionsStatus();
        }

        return Command::SUCCESS;
    }

    protected function renderAccountsStatus(): void
    {
        $table = $this->createTable();
        $table->setHeaders(['Exchange', 'Amount']);

        /** @var Account[] $accounts */
        $accounts = $this->accountRepository->findAll();
        foreach ($accounts as $account) {
            $table->addRow([
                $account->getExchange()->value,
                MoneyHelper::pretty($account->getAmount()),
            ]);
        }

        $table->setVertical();
        $table->render();

        $this->sendTelegramOutput('Accounts status');
    }

    protected function renderPositionsStatus(): void
    {
        $exchange = new binance([
            'timeout' => 30000,
            'options' => [
                'defaultType' => 'future',
            ],
        ]);

        $table = $this->createTable();
        $table->setHeaders(['Ticker', 'ROI %', 'Direction']);

        /** @var Position[] $positions */
        $positions = $this->positionRepository->findBy([
            'status' => PositionStatusEnum::Open,
        ], ['createdAt' => 'ASC']);

        $symbols = [];
        foreach ($positions as $position) {
            $intent = $position->getIntent();

            $symbols[TickerHelper::tickerToSymbol($intent->getTicker()->getName())] = [
                'direction' => $intent->getDirection(),
                'entryPrice' => $position->getEntryPrice(),
            ];
        }

        $tickers = $exchange->fetch_tickers(array_keys($symbols));
        foreach ($tickers as $symbol => $tickerData) {
            $direction = $symbols[$symbol]['direction'];
            $roi = TradingSimulator::calculateRoi($tickerData['last'], $symbols[$symbol]['entryPrice'], $direction);

            $table->addRow([
                TickerHelper::symbolToTicker($symbol),
                round($roi * 100, 3),
                $direction->name,
            ]);
        }
        $table->render();

        $this->sendTelegramOutput('Positions status');
    }

    private function createTable(): Table
    {
        $useTelegram = false !== $this->input->getOption('telegram');
        if ($useTelegram) {
            $this->bufferedOutput = new BufferedOutput();

            return new Table($this->bufferedOutput);
        }

        return $this->io->createTable();
    }

    private function sendTelegramOutput(string $title): void
    {
        $useTelegram = false !== $this->input->getOption('telegram');

        if (!$useTelegram || null === $this->bufferedOutput) {
            return;
        }

        $message = '<b>📖 '.$title.'</b>'.PHP_EOL.PHP_EOL;
        $message .= '<pre>'.$this->bufferedOutput->fetch().'</pre>';
        $this->eventDispatcher->dispatch(
            new TelegramLogEvent($message)
        );
    }
}
