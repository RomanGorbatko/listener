<?php

namespace App\Trader;

use App\Entity\Position;
use App\Enum\DirectionEnum;
use App\Enum\PositionStatusEnum;
use App\Event\TelegramLogEvent;
use App\Helper\MoneyHelper;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currencies\CryptoCurrencies;
use Money\Currency;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;
use Money\MoneyFormatter;
use Money\MoneyParser;
use Money\Parser\DecimalMoneyParser;
use Sentry\State\Scope;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function Sentry\configureScope;

#[Autoconfigure(shared: false)]
class TradingSimulator
{
    private const float STOP_LOSS_FACTOR_LONG = 0.98; // 2% для long
    private const float TAKE_PROFIT_FACTOR_LONG = 1.02; // 2% для long
    private const float STOP_LOSS_FACTOR_SHORT = 1.02; // 2% для short
    private const float TAKE_PROFIT_FACTOR_SHORT = 0.98; // 2% для short

//    private const float STOP_LOSS_FACTOR_LONG = 0.99; // 1% для long
//    private const float TAKE_PROFIT_FACTOR_LONG = 1.01; // 1% для long
//    private const float STOP_LOSS_FACTOR_SHORT = 1.01; // 1% для short
//    private const float TAKE_PROFIT_FACTOR_SHORT = 0.99; // 1% для short

    private const float TRAILING_STEP = 0.0025; // 0.2%
    private const float COMMISSION_RATE = 0.001;

    public function __construct(
        private readonly Position $position,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    public function openPosition(): void
    {
        if ($this->position->getStatus() === PositionStatusEnum::Open) {
            return;
        }

        $this->position->setStatus(PositionStatusEnum::Open);
        $this->position->setAmount($this->position->getAccount()->getAmount()->multiply((string) $this->position->getRisk()));

        $commission = $this->position->getAmount()?->multiply($this->position->getLeverage())->multiply((string) self::COMMISSION_RATE);
        $this->position->setCommission($commission);
        $this->position->setStopLossPrice($this->position->getEntryPrice() * ($this->position->getIntent()->getDirection() === DirectionEnum::Long ? self::STOP_LOSS_FACTOR_LONG : self::STOP_LOSS_FACTOR_SHORT));
        $this->position->setTakeProfitPrice($this->position->getEntryPrice() * ($this->position->getIntent()->getDirection() === DirectionEnum::Long ? self::TAKE_PROFIT_FACTOR_LONG : self::TAKE_PROFIT_FACTOR_SHORT));

        $this->position->getAccount()->setAmount(
            $this->position->getAccount()->getAmount()->subtract(
                $this->position->getAmount()?->add($this->position->getCommission())
            )
        );

        $logMessage = '🫡 <b>Position opened</b>' . PHP_EOL;
        $logMessage .= 'Ticker: <i>#' . $this->position->getIntent()->getTicker()->getName() . '</i>' . PHP_EOL;
        $logMessage .= 'Direction: <i>' . $this->position->getIntent()->getDirection()->name . '</i>' . PHP_EOL;
        $logMessage .= 'Entry: <i>' . $this->position->getEntryPrice() . '</i>' . PHP_EOL;
        $logMessage .= 'Take Profit: <i>' . $this->position->getTakeProfitPrice() . '</i>' . PHP_EOL;
        $logMessage .= 'Stop Loss: <i>' . $this->position->getStopLossPrice() . '</i>' . PHP_EOL;
        $logMessage .= 'Balance: <i>' . MoneyHelper::formater()->format($this->position->getAccount()->getAmount()) . '</i>';
        $this->eventDispatcher->dispatch(new TelegramLogEvent($logMessage));
    }

    private function closePosition(float $exitPrice, Money $partialAmount = null): void
    {
        $amountToClose = $partialAmount ?: $this->position->getAmount();

        $multiplier = 0;
        if ($this->position->getIntent()->getDirection() === DirectionEnum::Long) {
            $multiplier = $exitPrice - $this->position->getEntryPrice();
        } elseif ($this->position->getIntent()->getDirection() === DirectionEnum::Short) {
            $multiplier = $this->position->getEntryPrice() - $exitPrice;
        }

        configureScope(function (Scope $scope) use($exitPrice, $multiplier): void {
            $scope->setContext('closePosition', [
                'positionId' => $this->position->getId(),
                'exitPrice' => $exitPrice,
                'entryPrice' => $this->position->getEntryPrice(),
                'multiplier' => $multiplier,
            ]);
        });

        $profit = $amountToClose?->multiply($this->position->getLeverage())
            ->multiply((string) $multiplier)
            ->divide((string) $this->position->getEntryPrice());

        $commission = $amountToClose?->multiply($this->position->getLeverage())
            ->multiply((string) self::COMMISSION_RATE);

        $this->position->setCommission(
            $this->position->getCommission()?->add(
                $commission
            )
        );

        if ($this->position->getPnl() === null) {
            $this->position->setPnl($profit);
        } else {
            $this->position->setPnl(
                $this->position->getPnl()->add($profit)
            );
        }

        $this->position->getAccount()->setAmount(
            $this->position->getAccount()->getAmount()
                ->add($amountToClose)
                ->add($this->position->getPnl())
                ->subtract($commission)
        );

        if ($partialAmount === null) {
            $logMessage = '🫡 <b>Position closed</b>' . PHP_EOL;

            $this->position->setAmount(MoneyHelper::createZeroMoney());
            $this->position->setStatus(PositionStatusEnum::Closed);
        } else {
            $logMessage = '🫡 <b>Position closed partially</b>' . PHP_EOL;

            $this->position->setAmount(
                $this->position->getAmount()?->subtract($partialAmount)
            );
        }

        $logMessage .= 'Ticker: <i>#' . $this->position->getIntent()->getTicker()->getName() . '</i>' . PHP_EOL;
        $logMessage .= 'Direction: <i>' . $this->position->getIntent()->getDirection()->name . '</i>' . PHP_EOL;
        $logMessage .= 'Profit: <i>' . MoneyHelper::formater()->format($profit) . '</i>' . PHP_EOL;
        $logMessage .= 'Balance: <i>' . MoneyHelper::formater()->format($this->position->getAccount()->getAmount()) . '</i>';
        $this->eventDispatcher->dispatch(new TelegramLogEvent($logMessage));

//        $this->balance += ($amountToClose + $profit - $commission);
//        $this->updateAccountBalance();
//        $this->totalCommissions += $commission;
//
//        if ($partialAmount !== null && $partialAmount < $this->position->getAmount()) {
//            $this->position->setAmount($this->position->getAmount() - $partialAmount);
//
//            $logMessage = '🫡 <b>Position closed partially</b>' . PHP_EOL;
//        } else {
//            $logMessage = '🫡 <b>Position closed</b>' . PHP_EOL;
//
//            $this->position->setAmount(0);
//            $this->position->setPnl($profit);
//            $this->position->setStatus(PositionStatusEnum::Closed);
//        }
//
    }

    public function updateTrailing(float $currentPrice): void
    {
        if ($this->position->getStatus() === PositionStatusEnum::Closed) {
            return;
        }

        $entryPrice = $this->position->getEntryPrice();
        $currentStopLoss = $this->position->getStopLossPrice();
        $currentTakeProfit = $this->position->getTakeProfitPrice();
        $stopLossMultiplier = $this->position->isClosedPartially() ? 0.01 : 0.02;

        if ($this->position->getIntent()->getDirection() === DirectionEnum::Long) {
            // Розрахунок приросту ціни у відсотках від точки входу
            $priceIncreasePercent = ($currentPrice - $entryPrice) / $entryPrice;
            // Розрахунок нового стоп-лоссу на основі приросту ціни
            $newStopLossPercent = floor($priceIncreasePercent * 100) / 100 - $stopLossMultiplier; // Віднімаємо 2%, щоб підняти стоп кожні 1%
            $newStopLoss = $entryPrice * (1 + $newStopLossPercent);

            // Оновлюємо стоп-лосс, якщо він вищий за поточний
            if ($newStopLoss > $currentStopLoss) {
                $this->position->setStopLossPrice($newStopLoss);

                $logMessage = '🫡 <b>Stop loss trailed</b>' . PHP_EOL;
                $logMessage .= 'Ticker: <i>#' . $this->position->getIntent()->getTicker()->getName() . '</i>' . PHP_EOL;
                $logMessage .= 'Stop loss: <i>' . $this->position->getStopLossPrice() . '</i>';
                $this->eventDispatcher->dispatch(new TelegramLogEvent($logMessage));
            }

            // Якщо ціна досягла поточного take-profit
            if ($currentPrice >= $currentTakeProfit) {
                if ($this->position->isClosedPartially() === false) {
                    // Закриваємо 70% позиції
                    $partialAmount = $this->position->getAmount()?->multiply((string) 0.70);
                    $this->closePosition($currentPrice, $partialAmount);

//                    // Оновлюємо суму позиції на 30% від початкової
//                    $this->position->setAmount($this->position->getAmount()?->multiply((string) 0.30));
                    $this->position->setClosedPartially(true);
                }

                // Оновлюємо take-profit, збільшуючи його на 0.25%
                $newTakeProfit = $currentTakeProfit * (1 + self::TRAILING_STEP);
                if ($currentPrice >= $newTakeProfit) {
                    $newTakeProfit = $currentPrice * (1 + self::TRAILING_STEP);
                }

                $this->position->setTakeProfitPrice($newTakeProfit);

                $logMessage = '🫡 <b>Take profit trailed</b>' . PHP_EOL;
                $logMessage .= 'Ticker: <i>#' . $this->position->getIntent()->getTicker()->getName() . '</i>' . PHP_EOL;
                $logMessage .= 'Take profit: <i>' . $this->position->getTakeProfitPrice() . '</i>';
                $this->eventDispatcher->dispatch(new TelegramLogEvent($logMessage));
            }
        }

        if ($this->position->getIntent()->getDirection() === DirectionEnum::Short) {
            $priceDecreasePercent = ($entryPrice - $currentPrice) / $entryPrice;

            $newStopLossPercent = floor($priceDecreasePercent * 100) / 100 - $stopLossMultiplier;
            $newStopLoss = $entryPrice * (1 - $newStopLossPercent);

            if ($newStopLoss < $currentStopLoss) {
                $this->position->setStopLossPrice($newStopLoss);

                $logMessage = '🫡 <b>Stop loss trailed</b>' . PHP_EOL;
                $logMessage .= 'Ticker: <i>#' . $this->position->getIntent()->getTicker()->getName() . '</i>' . PHP_EOL;
                $logMessage .= 'Stop loss: <i>' . $this->position->getStopLossPrice() . '</i>';
                $this->eventDispatcher->dispatch(new TelegramLogEvent($logMessage));
            }

            if ($currentPrice <= $currentTakeProfit) {
                if ($this->position->isClosedPartially() === false) {
                    $partialAmount = $this->position->getAmount()?->multiply((string) 0.70);
                    $this->closePosition($currentPrice, $partialAmount);

//                    $this->position->setAmount($this->position->getAmount()?->multiply((string) 0.30));
                    $this->position->setClosedPartially(true);
                }

                $newTakeProfit = $currentTakeProfit * (1 - self::TRAILING_STEP);
                if ($currentPrice >= $newTakeProfit) {
                    $newTakeProfit = $currentPrice * (1 - self::TRAILING_STEP);
                }
                $this->position->setTakeProfitPrice($newTakeProfit);

                $logMessage = '🫡 <b>Take profit trailed</b>' . PHP_EOL;
                $logMessage .= 'Ticker: <i>#' . $this->position->getIntent()->getTicker()->getName() . '</i>' . PHP_EOL;
                $logMessage .= 'Take profit: <i>' . $this->position->getTakeProfitPrice() . '</i>';
                $this->eventDispatcher->dispatch(new TelegramLogEvent($logMessage));
            }
        }
    }

    public function checkPosition(float $currentPrice): void
    {
        if ($this->position->getStatus() === PositionStatusEnum::Closed) {
            return;
        }

        if ($this->position->getIntent()->getDirection() === DirectionEnum::Long) {
            if ($currentPrice <= $this->position->getStopLossPrice()) {
                $this->closePosition($currentPrice);
                return;
            }

            if ($currentPrice >= $this->position->getTakeProfitPrice()) {
                $this->closePosition($currentPrice);
                return;
            }
        }

        if ($this->position->getIntent()->getDirection() === DirectionEnum::Short) {
            if ($currentPrice >= $this->position->getStopLossPrice()) {
                $this->closePosition($currentPrice);
                return;
            }

            if ($currentPrice <= $this->position->getTakeProfitPrice()) {
                $this->closePosition($currentPrice);
                return;
            }
        }

        return;
    }

    private function updateAccountBalance(): void
    {
        $this->entityManager->refresh($this->position->getAccount());

        $this->position->getAccount()->setAmount(
            MoneyHelper::parser()->parse(
                (string) $this->balance,
                $this->position->getAccount()->getAmount()->getCurrency()
            )
        );
    }
}
