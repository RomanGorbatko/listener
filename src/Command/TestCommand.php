<?php

namespace App\Command;

use App\Entity\Account;
use App\Enum\ExchangeEnum;
use App\Enum\UpdateAccountBalanceTypeEnum;
use App\Event\TelegramLogEvent;
use App\Helper\MoneyHelper;
use App\Message\CryptoAttackNotification;
use App\Processor\Handler\CexTrackProcessorHandler;
use App\Repository\AccountRepository;
use App\Service\CurrencyExchange;
use App\Trader\TradeManager;
use Brick\Money\Currency;
use Brick\Money\Money;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:test',
    description: 'Add a short description for your command',
)]
class TestCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CexTrackProcessorHandler $cexTrackProcessorHandler,
        private readonly TradeManager $tradeManager,
        private readonly CurrencyExchange $currencyExchange,
        private readonly AccountRepository $accountRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Account $account */
        $account = $this->accountRepository->findOneBy(['exchange' => ExchangeEnum::BinanceFutures]);

        $balance = $this->accountRepository->getMinorBalance($account);
        dump(MoneyHelper::pretty(MoneyHelper::ofMinorMoney($balance)));

        $this->accountRepository->updateBalance($account, MoneyHelper::createMoney(150), UpdateAccountBalanceTypeEnum::Decrease);

        $balance = $this->accountRepository->getMinorBalance($account);
        dump(MoneyHelper::pretty(MoneyHelper::ofMinorMoney($balance)));

        exit;

        $usdt = MoneyHelper::createMoney(1000);
        $btc = MoneyHelper::createMoney(1, new Currency('BTC', 0, 'Bitcoin', 8));

        dump($usdt->plus($this->currencyExchange->convert($btc)));

        exit;

        //        $bitcoin = new Currency(currencyCode: 'BTC', numericCode: 0, name: 'Bitcoin', defaultFractionDigits: 8);
        //        $money = Money::of('0.123', $bitcoin); // XBT 0.12300000
        //
        //        dump($money->getMinorAmount()->toInt());
        //
        //        //        throw new \RuntimeException('Example exception.');
        //        //        $this->eventDispatcher->dispatch(new TelegramLogEvent('test text'));
        //        exit;

        //        $risk = 0.1;
        //        $commissionRate = 0.001;
        //        $leverage = 20;
        //        $initialBalance = 1000;
        //        $entryPrice = 1000;
        //        $stopPrice = 980;
        //        $takeProfitPrice =
        //        $currency = new Currency(MoneyHelper::BASE_CURRENCY);
        //
        //        $account = MoneyHelper::parser()->parse($initialBalance, $currency);
        //        $positionSize = $account->multiply((string) $risk);
        //        $effectiveAmount = $positionSize->multiply($leverage);
        //        $commissionPaid = $effectiveAmount->multiply((string) $commissionRate);
        //
        //        $account = $account->subtract(
        //            $positionSize->add($commissionPaid)
        //        );
        //
        //        dump([
        //            'positionSize' => MoneyHelper::formater()->format($positionSize),
        //            'effectiveAmount' => MoneyHelper::formater()->format($effectiveAmount),
        //            'commissionPaid' => MoneyHelper::formater()->format($commissionPaid),
        //            'accountAfterOpenedPosition' => MoneyHelper::formater()->format($account),
        //        ]);

        //        exit;

        $this->bus->dispatch(new CryptoAttackNotification('
🔥 #LTC $69.07 4.91% CEX (ByBit) 1 hour price change after notification:
 Time UTC: 24.10.15 14:50:15
🆕📊📶 Tracking the dynamics of changes in trading volumes (5 min) #DTVolumesnew

#LTC $66 (1h: -1,52%, 24h: -0,85%)
tweet: 783 +15% (Vol 24h: +35,99%, +2,21)
🥳 40%, 😑 42%, 🤬 18%
        ', new \DateTimeImmutable()));
        //        $this->bus->dispatch(new CryptoAttackNotification('
        // 📊🧨 Top 10 Selling coins on Binance Futures in the last 60 minutes (amount) #TopCEXfb
        // #ETH buy: $209119226 sell: $225458526
        // delta: -$16339300 (0,2%; Vol24: 8075,57M)
        //
        // #BCH buy: $12376413 sell: $15921462
        // delta: -$3545049 (1,85%; Vol24: 191,33M)
        //
        // #ALT buy: $12378731 sell: $14182448
        // delta: -$1803716 (1,27%; Vol24: 141,02M)
        //
        // #BLZ buy: $3505970 sell: $4798389
        // delta: -$1292420 (3,22%; Vol24: 40,04M)
        //
        // #DOGE buy: $9756805 sell: $10871960
        // delta: -$1115156 (0,31%; Vol24: 356,76M)
        //
        // #LTC buy: $2921702 sell: $3914544
        // delta: -$992842 (0,9%; Vol24: 110,25M)
        //
        // #DYDX buy: $2008060 sell: $2990526
        // delta: -$982466 (1,02%; Vol24: 95,90M)
        //
        // #1000PEPE buy: $19152613 sell: $20108347
        // delta: -$955734 (0,1%; Vol24: 943,33M)
        //
        // #SAGA buy: $4393017 sell: $5296617
        // delta: -$903600 (0,17%; Vol24: 505,75M)
        //
        // #WLD buy: $5620421 sell: $6511798
        // delta: -$891377 (0,31%; Vol24: 285,22M)
        //        ', new \DateTimeImmutable()));

        return Command::SUCCESS;
    }
}
