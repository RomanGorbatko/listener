<?php

namespace App\Trader;

use App\Entity\Account;
use App\Entity\Intent;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Enum\IntentStatusEnum;
use App\Enum\PositionStatusEnum;
use App\Event\TelegramLogEvent;
use App\Helper\MoneyHelper;
use App\Helper\TickerHelper;
use App\Repository\AccountRepository;
use App\Repository\PositionRepository;
use App\Service\RedisService;
use ccxt\binanceusdm as BinanceClassic;
use ccxt\pro\binanceusdm as BinanceAsync;
use Doctrine\ORM\EntityManagerInterface;
use React\Promise\PromiseInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use function React\Async\async;
use function React\Async\await;

readonly class TradeManager
{
    private const int DEFAULT_LEVERAGE = 20;
    //    private const float DEFAULT_RISK_PERCENTAGE = 0.1; // 10%
    private const float DEFAULT_RISK_PERCENTAGE = 0.01; // 1%

    public function __construct(
        private int $minimumBalance,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $eventDispatcher,
        private PositionRepository $positionRepository,
        private AccountRepository $accountRepository,
        private TradeMap $tradeMap,
        private RedisService $redisService,
    ) {
    }

    public function isTradeAllowed(): bool
    {
        $minimumBalance = MoneyHelper::createMoney($this->minimumBalance);
        $balance = MoneyHelper::createZeroMoney();

        /** @var Account[] $accounts */
        $accounts = $this->accountRepository->findAll();
        foreach ($accounts as $account) {
            $balance = $balance->plus($account->getAmount());
        }

        $minimumBalance = $minimumBalance->plus(
            count($this->getPositionsForListen()) * self::DEFAULT_RISK_PERCENTAGE * 1000
        );

        return $minimumBalance->isLessThan($balance);
    }

    public function openPosition(Intent $intent, Account $account)
    {
        $isPositionExists = $this->positionRepository->isOpenedPositionExists(
            $intent->getTicker(), $account->getExchange()
        );

        if ($isPositionExists) {
            // @todo refactor with actual exception
            throw new \Exception('Position already exists.');
        }

        $openPrice = $this->openPositionOnExchange($intent->getTicker());

        if (null === $openPrice) {
            $intent->setStatus(IntentStatusEnum::Closed);

            $this->entityManager->persist($intent);
            $this->entityManager->flush();

            return;
        }

        $position = new Position();
        $position->setIntent($intent);
        $position->setAccount($account);
        $position->setStatus(PositionStatusEnum::Ready);
        $position->setRisk(self::DEFAULT_RISK_PERCENTAGE);
        $position->setLeverage(self::DEFAULT_LEVERAGE);
        $position->setEntryPrice($openPrice);

        $intent->setStatus(IntentStatusEnum::OnPosition);
        $this->entityManager->persist($intent);

        $this->entityManager->persist($position);
        $this->entityManager->flush();
    }

    public function listenOpenPositions(): PromiseInterface
    {
        return async(function () {
            $binance = new BinanceAsync([]);
            $symbols = [];

            while (true) {
                if (date('s') % 5) {
                    $symbols = $this->refreshSymbols();
                    sleep(1);
                }

                usleep(10);

                if (!$symbols) {
                    continue;
                }

                $tickers = await($binance->watch_tickers($symbols));
                foreach ($tickers as $ticker) {
                    /** @var TradingSimulator $tradingSimulator */
                    $tradingSimulator = $this->tradeMap->getTrade($ticker['symbol']);
                    if (!$tradingSimulator instanceof TradingSimulator) {
                        continue;
                    }

                    $tradingSimulator->updateTrailing($ticker['last']);
                    $tradingSimulator->checkPosition($ticker['last']);

                    $this->commitPosition($tradingSimulator->getPosition(), $ticker['symbol']);
                }
            }
        })();
    }

    private function refreshSymbols(): array
    {
        $symbols = [];
        $positions = $this->getPositionsForListen();

        foreach ($positions as $position) {
            $symbol = TickerHelper::tickerToSymbol(
                $position->getIntent()->getTicker()->getName()
            );

            if (null === $this->tradeMap->getTrade($symbol)) {
                $tradingSimulator = new TradingSimulator($position, $this->eventDispatcher, $this->accountRepository);
                $tradingSimulator->openPosition();

                $this->commitPosition($tradingSimulator->getPosition(), $symbol);

                $this->tradeMap->setTrade($symbol, $tradingSimulator);
            }

            $symbols[] = $symbol;
        }

        return $symbols;
    }

    private function commitPosition(Position $position, string $ticker): void
    {
        $this->entityManager->beginTransaction();
        try {
            $this->entityManager->persist($position);
            $this->entityManager->persist($position->getAccount());

            if (PositionStatusEnum::Closed === $position->getStatus()) {
                $this->redisService->processTradeCostData($position->getIntent());

                $position->getIntent()->setStatus(IntentStatusEnum::Closed);

                $this->entityManager->persist($position->getIntent());

                $this->tradeMap->removeTrade($ticker);

                $this->redisService->clearTradeCostsData($position->getIntent(), IntentStatusEnum::OnPosition);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $logMessage = '⚡️<b>Exception occurs TradeManager</b>'.PHP_EOL;
            $logMessage .= 'Class: <i>'.get_class($throwable).'</i>'.PHP_EOL;
            $logMessage .= 'Message: <i>'.$throwable->getMessage().'</i>';
            $this->eventDispatcher->dispatch(new TelegramLogEvent($logMessage));

            $this->entityManager->rollback();
        }
    }

    /**
     * @return Position[]
     */
    private function getPositionsForListen(): array
    {
        return $this->positionRepository->findBy(['status' => [
            PositionStatusEnum::Ready,
            PositionStatusEnum::Open,
        ]]);
    }

    private function openPositionOnExchange(Ticker $ticker, int $retry = 0): ?float
    {
        if ($retry > 5) {
            return null;
        }

        $binance = new BinanceClassic([]);
        $orderBook = $binance->fetch_order_book(
            TickerHelper::tickerToSymbol($ticker->getName())
        );

        if (isset($orderBook['asks'][0][0])) {
            return (float) $orderBook['asks'][0][0];
        }

        return $this->openPositionOnExchange($ticker, ++$retry);
    }
}
