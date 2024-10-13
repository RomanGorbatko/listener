<?php

namespace App\Trader;

use App\Entity\Position;
use App\Enum\DirectionEnum;
use App\Enum\PositionStatusEnum;
use App\Event\TelegramLogEvent;
use App\Helper\MoneyHelper;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currencies\CryptoCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Money\MoneyFormatter;
use Money\MoneyParser;
use Money\Parser\DecimalMoneyParser;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    private const float TRAILING_STEP = 0.002; // 0.2%
    private const float COMMISSION_RATE = 0.001;

    private float $balance;
    private float $totalCommissions = 0; // Загальна сума витрачених на комісії грошей
    private float $effectiveAmount = 0;
    private bool $closedPartial = false;

    public function __construct(
        private readonly Position $position,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->balance = (float) MoneyHelper::formater()->format($position->getAccount()->getAmount());
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    public function openPosition(): void
    {
        if ($this->position->getStatus() === PositionStatusEnum::Open) {
            $this->effectiveAmount = $this->position->getAmount() * $this->position->getLeverage();
            $commission = $this->effectiveAmount * self::COMMISSION_RATE;
            $this->totalCommissions += $commission;

            return;
        }

        $this->position->setStatus(PositionStatusEnum::Open);
        $this->position->setAmount($this->balance * $this->position->getRisk());
        $this->effectiveAmount = $this->position->getAmount() * $this->position->getLeverage();
        $commission = $this->effectiveAmount * self::COMMISSION_RATE;
        $this->totalCommissions += $commission;

        $this->position->setStopLossPrice($this->position->getOpenPrice() * ($this->position->getIntent()->getDirection() === DirectionEnum::Long ? self::STOP_LOSS_FACTOR_LONG : self::STOP_LOSS_FACTOR_SHORT));
        $this->position->setTakeProfitPrice($this->position->getOpenPrice() * ($this->position->getIntent()->getDirection() === DirectionEnum::Long ? self::TAKE_PROFIT_FACTOR_LONG : self::TAKE_PROFIT_FACTOR_SHORT));

        $this->balance -= ($this->position->getAmount() + $commission);
        $this->updateAccountBalance();

        $logMessage = '🫡 <b>Position opened</b>' . PHP_EOL;
        $logMessage .= 'Ticker: <i>#' . $this->position->getIntent()->getTicker()->getName() . '</i>' . PHP_EOL;
        $logMessage .= 'Entry: <i>' . $this->position->getOpenPrice() . '</i>' . PHP_EOL;
        $logMessage .= 'Take Profit: <i>' . $this->position->getTakeProfitPrice() . '</i>' . PHP_EOL;
        $logMessage .= 'Stop Loss: <i>' . $this->position->getStopLossPrice() . '</i>' . PHP_EOL;
        $logMessage .= 'Balance: <i>' . MoneyHelper::formater()->format($this->position->getAccount()->getAmount()) . '</i>';
        $this->eventDispatcher->dispatch(new TelegramLogEvent($logMessage));
    }

    public function closePosition(float $exitPrice, $partialAmount = null): void
    {
        $amountToClose = $partialAmount ?? $this->position->getAmount();
        $profit = 0;

        if ($this->position->getIntent()->getDirection() === DirectionEnum::Long) {
            $profit = ($exitPrice - $this->position->getOpenPrice()) * $this->effectiveAmount / $this->position->getOpenPrice();
        }

        if ($this->position->getIntent()->getDirection() === DirectionEnum::Short) {
            $profit = ($this->position->getOpenPrice() - $exitPrice) * $this->effectiveAmount / $this->position->getOpenPrice();
        }

        $commission = $this->effectiveAmount * self::COMMISSION_RATE;
        $this->balance += ($amountToClose + $profit - $commission);
        $this->updateAccountBalance();
        $this->totalCommissions += $commission;

        if ($partialAmount !== null && $partialAmount < $this->position->getAmount()) {
            $this->position->setAmount($this->position->getAmount() - $partialAmount);

            $logMessage = '🫡 <b>Position closed partialy</b>' . PHP_EOL;
        } else {
            $logMessage = '🫡 <b>Position closed</b>' . PHP_EOL;

            $this->position->setAmount(0);
            $this->position->setPnl($profit);
            $this->position->setStatus(PositionStatusEnum::Closed);
        }

        $logMessage .= 'Ticker: <i>#' . $this->position->getIntent()->getTicker()->getName() . '</i>' . PHP_EOL;
        $logMessage .= 'Profit: <i>' . $profit . '</i>' . PHP_EOL;
        $logMessage .= 'Balance: <i>' . MoneyHelper::formater()->format($this->position->getAccount()->getAmount()) . '</i>';
        $this->eventDispatcher->dispatch(new TelegramLogEvent($logMessage));
    }
    public function updateTrailing(float $currentPrice): void
    {
        if ($this->position->getStatus() === PositionStatusEnum::Closed) {
            return;
        }

        $entryPrice = $this->position->getOpenPrice();
        $currentStopLoss = $this->position->getStopLossPrice();
        $currentTakeProfit = $this->position->getTakeProfitPrice();

        if ($this->position->getIntent()->getDirection() === DirectionEnum::Long) {
            // Розрахунок приросту ціни у відсотках від точки входу
            $priceIncreasePercent = ($currentPrice - $entryPrice) / $entryPrice;
            // Розрахунок нового стоп-лоссу на основі приросту ціни
            $newStopLossPercent = floor($priceIncreasePercent * 100) / 100 - 0.02; // Віднімаємо 2%, щоб підняти стоп кожні 1%
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
                if ($this->closedPartial === false) {
                    // Закриваємо 75% позиції
                    $partialAmount = $this->position->getAmount() * 0.75;
                    $this->closePosition($currentPrice, $partialAmount);

                    // Оновлюємо суму позиції на 25% від початкової
                    $this->position->setAmount($this->position->getAmount() * 0.25);
                    $this->closedPartial = true;
                }

                // Оновлюємо take-profit, збільшуючи його на 0.2%
                $newTakeProfit = $currentTakeProfit * (1 + self::TRAILING_STEP);
                $this->position->setTakeProfitPrice($newTakeProfit);

                $logMessage = '🫡 <b>Take profit trailed</b>' . PHP_EOL;
                $logMessage .= 'Ticker: <i>#' . $this->position->getIntent()->getTicker()->getName() . '</i>' . PHP_EOL;
                $logMessage .= 'Take profit: <i>' . $this->position->getTakeProfitPrice() . '</i>';
                $this->eventDispatcher->dispatch(new TelegramLogEvent($logMessage));
            }
        }

        // Подібна логіка для short позиції, але адаптована до напрямку падіння ціни
        if ($this->position->getIntent()->getDirection() === DirectionEnum::Short) {
            $priceDecreasePercent = ($entryPrice - $currentPrice) / $entryPrice;

            $newStopLossPercent = floor($priceDecreasePercent * 100) / 100 - 0.02;
            $newStopLoss = $entryPrice * (1 - $newStopLossPercent);

            if ($newStopLoss < $currentStopLoss) {
                $this->position->setStopLossPrice($newStopLoss);

                $logMessage = '🫡 <b>Stop loss trailed</b>' . PHP_EOL;
                $logMessage .= 'Ticker: <i>#' . $this->position->getIntent()->getTicker()->getName() . '</i>' . PHP_EOL;
                $logMessage .= 'Stop loss: <i>' . $this->position->getStopLossPrice() . '</i>';
                $this->eventDispatcher->dispatch(new TelegramLogEvent($logMessage));
            }

            if ($currentPrice <= $currentTakeProfit) {
                if ($this->closedPartial === false) {
                    $partialAmount = $this->position->getAmount() * 0.75;
                    $this->closePosition($currentPrice, $partialAmount);

                    $this->position->setAmount($this->position->getAmount() * 0.25);
                    $this->closedPartial = true;
                }

                $newTakeProfit = $currentTakeProfit * (1 - self::TRAILING_STEP);
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
