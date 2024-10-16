<?php

namespace App\Command\Account;

use App\Enum\ExchangeEnum;
use App\Helper\MoneyHelper;
use App\Helper\TickerHelper;
use App\Repository\AccountRepository;
use ccxt\binance;
use Redis;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'account:load-markets'
)]
class AccountLoadMarketsCommand extends Command
{
    public function __construct(
        private readonly Redis $redisDefault,
        private readonly AccountRepository $accountRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $accountExchanges = $this->accountRepository->getAvailableAccountExchanges();
        foreach ($accountExchanges as $accountExchange) {
            match ($accountExchange) {
                ExchangeEnum::BinanceFutures => $this->loadBinanceFuturesMarkets(),
            };

            $io->success(sprintf('%s market successfully loaded.', $accountExchange->value));
        }

        return Command::SUCCESS;
    }

    private function loadBinanceFuturesMarkets(): void
    {
        $key = sprintf('%s_%s', ExchangeEnum::BinanceFutures->value, 'markets');

        $exchange = new binance([
            'options' => [
                'defaultType' => 'future',
            ]
        ]);

        $markets = $exchange->load_markets();
        foreach ($markets as $symbol => $market) {
            if (! ($market['quote'] === MoneyHelper::BASE_CURRENCY && $market['type'] === 'swap')) {
                continue;
            }

            $this->redisDefault->sAdd($key, TickerHelper::symbolToTicker($symbol));
        }
    }
}
