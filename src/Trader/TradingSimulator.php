<?php

namespace App\Trader;

use App\Entity\Account;
use App\Entity\Position;
use App\Enum\DirectionEnum;
use App\Enum\PositionStatusEnum;
use App\Enum\UpdateAccountBalanceTypeEnum;
use App\Event\TelegramLogEvent;
use App\Helper\MoneyHelper;
use App\Repository\AccountRepository;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
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

    private const float TRAILING_STEP = 0.0025; // 0.2%
    private const float COMMISSION_RATE = 0.0005;

    private const int CLOSE_STUCKED_AFTER_HOURS = 8;

    public function __construct(
        private readonly Position $position,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AccountRepository $accountRepository,
    ) {
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    public function openPosition(): void
    {
        if (PositionStatusEnum::Open === $this->position->getStatus()) {
            return;
        }

        $this->position->setStatus(PositionStatusEnum::Open);
        $this->position->setAmount(
            $this->getAccountBalance($this->position->getAccount())
                ->multipliedBy($this->position->getRisk(), RoundingMode::HALF_DOWN)
        );

        $commission = $this->position->getAmount()
            ?->multipliedBy($this->position->getLeverage(), RoundingMode::HALF_DOWN)
            ->multipliedBy(self::COMMISSION_RATE, RoundingMode::HALF_DOWN);
        $this->position->setCommission($commission);

        $stopLossPrice = $this->position->getEntryPrice() * (
            DirectionEnum::Long === $this->position->getIntent()->getDirection()
                ? self::STOP_LOSS_FACTOR_LONG
                : self::STOP_LOSS_FACTOR_SHORT
        )
        ;
        $this->position->setStopLossPrice($stopLossPrice);

        $takeProfitPrice = $this->position->getEntryPrice() * (
            DirectionEnum::Long === $this->position->getIntent()->getDirection()
                ? self::TAKE_PROFIT_FACTOR_LONG
                : self::TAKE_PROFIT_FACTOR_SHORT
        )
        ;
        $this->position->setTakeProfitPrice($takeProfitPrice);

        $logMessage = '🫡 <b>Position opened</b>'.PHP_EOL;
        $logMessage .= 'Ticker: <i>#'.$this->position->getIntent()->getTicker()->getName().'</i>'.PHP_EOL;
        $logMessage .= 'Direction: <i>'.$this->position->getIntent()->getDirection()->name.'</i>'.PHP_EOL;
        $logMessage .= 'Entry: <i>'.$this->position->getEntryPrice().'</i>'.PHP_EOL;
        $logMessage .= 'Take Profit: <i>'.$this->position->getTakeProfitPrice().'</i>'.PHP_EOL;
        $logMessage .= 'Stop Loss: <i>'.$this->position->getStopLossPrice().'</i>'.PHP_EOL;
        $logMessage .= 'Balance: <i>'.MoneyHelper::pretty($this->getAccountBalance($this->position->getAccount())).'</i>';
        $this->eventDispatcher->dispatch(new TelegramLogEvent($logMessage));
    }

    private function closePosition(float $exitPrice, ?Money $partialAmount = null, bool $closeStucked = false): void
    {
        $amountToClose = $partialAmount ?: $this->position->getAmount();

        $multiplier = 0;
        if (DirectionEnum::Long === $this->position->getIntent()->getDirection()) {
            $multiplier = $exitPrice - $this->position->getEntryPrice();
        } elseif (DirectionEnum::Short === $this->position->getIntent()->getDirection()) {
            $multiplier = $this->position->getEntryPrice() - $exitPrice;
        }

        if (str_contains((string) $multiplier, 'E')) {
            $multiplier = sprintf('%.6f', $multiplier);
        }

        $profit = $amountToClose
            ?->multipliedBy($this->position->getLeverage(), RoundingMode::HALF_DOWN)
            ->multipliedBy($multiplier, RoundingMode::HALF_DOWN)
            ->dividedBy($this->position->getEntryPrice(), RoundingMode::HALF_DOWN);

        $commission = $amountToClose
            ?->multipliedBy($this->position->getLeverage(), RoundingMode::HALF_DOWN)
            ->multipliedBy(self::COMMISSION_RATE, RoundingMode::HALF_DOWN);

        $this->position->setCommission(
            $this->position->getCommission()?->plus(
                $commission
            )
        );

        if (null === $this->position->getPnl()) {
            $this->position->setPnl($profit);
        } else {
            $this->position->setPnl(
                $this->position->getPnl()->plus($profit)
            );
        }

        $this->updatedAccountBalance($this->position->getAccount(), $profit, UpdateAccountBalanceTypeEnum::Increase);

        if (null === $partialAmount) {
            $logMessage = '🫡 <b>Position closed'.($closeStucked ? ' [Stucked]' : '').'</b>'.PHP_EOL;

            $this->updatedAccountBalance($this->position->getAccount(), $this->position->getCommission(), UpdateAccountBalanceTypeEnum::Decrease);

            $this->position->setAmount(MoneyHelper::createZeroMoney());
            $this->position->setStatus(PositionStatusEnum::Closed);
        } else {
            $logMessage = '🫡 <b>Position closed partially</b>'.PHP_EOL;

            $this->position->setAmount(
                $this->position->getAmount()?->minus($partialAmount)
            );
        }

        $logMessage .= 'Ticker: <i>#'.$this->position->getIntent()->getTicker()->getName().'</i>'.PHP_EOL;
        $logMessage .= 'Direction: <i>'.$this->position->getIntent()->getDirection()->name.'</i>'.PHP_EOL;
        $logMessage .= 'Pnl: <i>'.MoneyHelper::pretty($this->position->getPnl()).'</i>'.PHP_EOL;
        $logMessage .= 'Commission: <i>'.MoneyHelper::pretty($this->position->getCommission()).'</i>'.PHP_EOL;
        $logMessage .= 'Profit: <i>'.MoneyHelper::pretty($this->position->getPnl()->minus($this->position->getCommission())).'</i>'.PHP_EOL;
        $logMessage .= 'Balance: <i>'.MoneyHelper::pretty($this->getAccountBalance($this->position->getAccount())).'</i>';
        $this->eventDispatcher->dispatch(new TelegramLogEvent($logMessage));
    }

    public function updateTrailing(float $currentPrice): void
    {
        if (PositionStatusEnum::Closed === $this->position->getStatus()) {
            return;
        }

        $entryPrice = $this->position->getEntryPrice();
        $currentStopLoss = $this->position->getStopLossPrice();
        $currentTakeProfit = $this->position->getTakeProfitPrice();
        $stopLossMultiplier = $this->position->isClosedPartially() ? 0.01 : 0.02;
        $direction = $this->position->getIntent()->getDirection();

        if (DirectionEnum::Long === $direction) {
            // Розрахунок приросту ціни у відсотках від точки входу
            $priceIncreasePercent = self::calculateRoi($currentPrice, $entryPrice, $direction);
            // Розрахунок нового стоп-лоссу на основі приросту ціни
            $newStopLossPercent = floor($priceIncreasePercent * 100) / 100 - $stopLossMultiplier; // Віднімаємо 2%, щоб підняти стоп кожні 1%
            $newStopLoss = $entryPrice * (1 + $newStopLossPercent);

            // Оновлюємо стоп-лосс, якщо він вищий за поточний
            if ($newStopLoss > $currentStopLoss) {
                $this->processStopLossTrailing($newStopLoss);
            }

            // Якщо ціна досягла поточного take-profit
            if ($currentPrice >= $currentTakeProfit) {
                if (false === $this->position->isClosedPartially()) {
                    // Закриваємо 70% позиції
                    $partialAmount = $this->position->getAmount()?->multipliedBy((string) 0.70, RoundingMode::HALF_DOWN);
                    $this->closePosition($currentPrice, $partialAmount);

                    $this->position->setClosedPartially(true);
                }

                // Оновлюємо take-profit, збільшуючи його на 0.25%
                $newTakeProfit = $currentTakeProfit * (1 + self::TRAILING_STEP);
                if ($currentPrice >= $newTakeProfit) {
                    $newTakeProfit = $currentPrice * (1 + self::TRAILING_STEP);
                }

                $this->processTakeProfitTrailing($newTakeProfit);
            }
        }

        if (DirectionEnum::Short === $direction) {
            $priceDecreasePercent = self::calculateRoi($currentPrice, $entryPrice, $direction);

            $newStopLossPercent = floor($priceDecreasePercent * 100) / 100 - $stopLossMultiplier;
            $newStopLoss = $entryPrice * (1 - $newStopLossPercent);

            if ($newStopLoss < $currentStopLoss) {
                $this->processStopLossTrailing($newStopLoss);
            }

            if ($currentPrice <= $currentTakeProfit) {
                if (false === $this->position->isClosedPartially()) {
                    $partialAmount = $this->position->getAmount()?->multipliedBy((string) 0.70, RoundingMode::HALF_DOWN);
                    $this->closePosition($currentPrice, $partialAmount);

                    $this->position->setClosedPartially(true);
                }

                $newTakeProfit = $currentTakeProfit * (1 - self::TRAILING_STEP);
                if ($currentPrice >= $newTakeProfit) {
                    $newTakeProfit = $currentPrice * (1 - self::TRAILING_STEP);
                }

                $this->processTakeProfitTrailing($newTakeProfit);
            }
        }
    }

    public function checkPosition(float $currentPrice): void
    {
        $this->closeStuckedPosition($currentPrice);

        if (PositionStatusEnum::Closed === $this->position->getStatus()) {
            return;
        }

        if (DirectionEnum::Long === $this->position->getIntent()->getDirection()) {
            if ($currentPrice <= $this->position->getStopLossPrice()) {
                $this->closePosition($currentPrice);

                return;
            }

            if ($currentPrice >= $this->position->getTakeProfitPrice()) {
                $this->closePosition($currentPrice);

                return;
            }
        }

        if (DirectionEnum::Short === $this->position->getIntent()->getDirection()) {
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

    public function processTakeProfitTrailing(float $newTakeProfit): void
    {
        if (null === $this->position->getOriginalTakeProfitPrice()) {
            $this->position->setOriginalTakeProfitPrice($this->position->getTakeProfitPrice());
        }

        $this->position->setTakeProfitTrailed($this->position->getTakeProfitTrailed() + 1);
        $this->position->setTakeProfitPrice($newTakeProfit);

        $logMessage = '🫡 <b>Take profit trailed</b>'.PHP_EOL;
        $logMessage .= 'Ticker: <i>#'.$this->position->getIntent()->getTicker()->getName().'</i>'.PHP_EOL;
        $logMessage .= 'Take profit: <i>'.$this->position->getTakeProfitPrice().'</i>';
        $this->eventDispatcher->dispatch(new TelegramLogEvent($logMessage));
    }

    public function processStopLossTrailing(float $newStopLoss): void
    {
        $this->position->setStopLossTrailed($this->position->getStopLossTrailed() + 1);
        $this->position->setStopLossPrice($newStopLoss);

        if (
            $this->position->getTakeProfitTrailed() >= 1
            && null !== $this->position->getOriginalTakeProfitPrice()
            && false === $this->position->isStopLossMovedToTakeProfit()
        ) {
            $this->position->setStopLossPrice($this->position->getOriginalTakeProfitPrice());
            $this->position->setStopLossMovedToTakeProfit(true);
        }

        $logMessage = '🫡 <b>Stop loss trailed</b>'.PHP_EOL;
        $logMessage .= 'Ticker: <i>#'.$this->position->getIntent()->getTicker()->getName().'</i>'.PHP_EOL;
        $logMessage .= 'Stop loss: <i>'.$this->position->getStopLossPrice().'</i>';
        $this->eventDispatcher->dispatch(new TelegramLogEvent($logMessage));
    }

    public static function calculateRoi(float $currentPrice, float $entryPrice, DirectionEnum $directionEnum): float
    {
        return match ($directionEnum) {
            DirectionEnum::Long => ($currentPrice - $entryPrice) / $entryPrice,
            DirectionEnum::Short => ($entryPrice - $currentPrice) / $entryPrice,
        };
    }

    protected function updatedAccountBalance(Account $account, Money $money, UpdateAccountBalanceTypeEnum $updateType): void
    {
        $this->accountRepository->updateBalance($account, $money, $updateType);
    }

    protected function getAccountBalance(Account $account): Money
    {
        return MoneyHelper::ofMinorMoney($this->accountRepository->getMinorBalance($account));
    }

    protected function closeStuckedPosition(float $currentPrice): void
    {
        $diff = (new \DateTime())->diff($this->position->getCreatedAt());
        $hours = $diff->h + ($diff->days * 24);

        if ($hours < self::CLOSE_STUCKED_AFTER_HOURS) {
            return;
        }

        $direction = $this->position->getIntent()->getDirection();
        $roi = self::calculateRoi($currentPrice, $this->position->getEntryPrice(), $direction);
        $roi = round($roi * 100, 3);

        if ($roi < 0) {
            return;
        }

        $this->closePosition($currentPrice, null, true);
    }
}
