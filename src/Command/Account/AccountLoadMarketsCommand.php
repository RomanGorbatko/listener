<?php

namespace App\Command\Account;

use App\Enum\ExchangeEnum;
use App\Helper\MoneyHelper;
use App\Helper\TickerHelper;
use App\Repository\AccountRepository;
use ccxt\binance;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'account:load-markets'
)]
class AccountLoadMarketsCommand extends Command
{
    public function __construct(
        private readonly \Redis $redisDefault,
        private readonly AccountRepository $accountRepository,
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
                ExchangeEnum::Simulator => throw new \Exception('To be implemented'),
                ExchangeEnum::Binance => throw new \Exception('To be implemented'),
                ExchangeEnum::Bybit => throw new \Exception('To be implemented'),
                ExchangeEnum::BybitFutures => throw new \Exception('To be implemented'),
                ExchangeEnum::Gate => throw new \Exception('To be implemented'),
                ExchangeEnum::Kucoin => throw new \Exception('To be implemented'),
                ExchangeEnum::Bitfinex => throw new \Exception('To be implemented'),
                ExchangeEnum::BitfinexFutures => throw new \Exception('To be implemented'),
                ExchangeEnum::Coinbase => throw new \Exception('To be implemented'),
                ExchangeEnum::Htx => throw new \Exception('To be implemented'),
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
            ],
        ]);

        $markets = $exchange->load_markets();
        foreach ($markets as $symbol => $market) {
            if (!(MoneyHelper::BASE_CURRENCY === $market['quote'] && 'swap' === $market['type'])) {
                continue;
            }

            $this->redisDefault->sAdd($key, TickerHelper::symbolToTicker($symbol));
        }
    }
}
