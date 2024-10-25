<?php

namespace App\Command;

use App\Entity\Account;
use App\Entity\Intent;
use App\Enum\ExchangeEnum;
use App\Enum\IntentStatusEnum;
use App\Enum\UpdateAccountBalanceTypeEnum;
use App\Event\TelegramLogEvent;
use App\Helper\MoneyHelper;
use App\Helper\TickerHelper;
use App\Message\CryptoAttackNotification;
use App\Processor\Handler\CexTrackProcessorHandler;
use App\Repository\AccountRepository;
use App\Repository\IntentRepository;
use App\Repository\PositionRepository;
use App\Service\CurrencyExchange;
use App\Trader\TradeManager;
use Brick\Money\Currency;
use Brick\Money\Money;
use ccxt\pro\binanceusdm as BinanceAsync;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

use function React\Async\coroutine;

#[AsCommand(
    name: 'app:test',
    description: 'Add a short description for your command',
)]
class TestCommand extends Command
{
    private ?PromiseInterface $promise = null;

    public function __construct(
        private readonly \Redis $redisDefault,
        private readonly MessageBusInterface $bus,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CexTrackProcessorHandler $cexTrackProcessorHandler,
        private readonly TradeManager $tradeManager,
        private readonly CurrencyExchange $currencyExchange,
        private readonly AccountRepository $accountRepository,
        private readonly PositionRepository $positionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly IntentRepository $intentRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
//        $this->bus->dispatch(new CryptoAttackNotification('
//🎰 #MBOX продают 🧨 на 158K USDT за 15 сек (11%) на Binance
//P: 0,817 ⬇️ (-1,21%)
//Объем за 24ч: 1M USDT
//Предыдущее 2 Д назад #CEXTrack
//        ', new \DateTimeImmutable()));
                $this->bus->dispatch(new CryptoAttackNotification('
         📊🧨 Top 10 Selling coins on Binance Futures in the last 60 minutes (amount) #TopCEXfb
         #MBOX buy: $209119226 sell: $225458526
         delta: -$16339300 (0,2%; Vol24: 8075,57M)
        
         #TRX buy: $12376413 sell: $15921462
         delta: -$3545049 (1,85%; Vol24: 191,33M)
        
         #ALT buy: $12378731 sell: $14182448
         delta: -$1803716 (1,27%; Vol24: 141,02M)
        
         #BLZ buy: $3505970 sell: $4798389
         delta: -$1292420 (3,22%; Vol24: 40,04M)
        
         #DOGE buy: $9756805 sell: $10871960
         delta: -$1115156 (0,31%; Vol24: 356,76M)
        
         #LTC buy: $2921702 sell: $3914544
         delta: -$992842 (0,9%; Vol24: 110,25M)
        
         #DYDX buy: $2008060 sell: $2990526
         delta: -$982466 (1,02%; Vol24: 95,90M)
        
         #1000PEPE buy: $19152613 sell: $20108347
         delta: -$955734 (0,1%; Vol24: 943,33M)
        
         #SAGA buy: $4393017 sell: $5296617
         delta: -$903600 (0,17%; Vol24: 505,75M)
        
         #WLD buy: $5620421 sell: $6511798
         delta: -$891377 (0,31%; Vol24: 285,22M)
                ', new \DateTimeImmutable()));

        return Command::SUCCESS;
    }
}
