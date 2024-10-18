<?php

namespace App\Tests\Unit\Trader;

use App\Entity\Account;
use App\Entity\Intent;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Enum\DirectionEnum;
use App\Enum\ExchangeEnum;
use App\Enum\IntentStatusEnum;
use App\Enum\PositionStatusEnum;
use App\Event\TelegramLogEvent;
use App\Helper\MoneyHelper;
use App\Trader\TradingSimulator;
use Brick\Money\Money;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase as TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TradingSimulatorTest extends TestCase
{
    private EventDispatcherInterface $eventDispatcher;

    public function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    public function testOpenPosition(): void
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(static fn ($event) => $event instanceof TelegramLogEvent)
            );

        $tradeSimulator = $this->openPosition();
        $position = $tradeSimulator->getPosition();

        $this->assertEquals(PositionStatusEnum::Open, $position->getStatus());
        $this->assertEquals('$100', $this->formatMoney($position->getAmount()));
        $this->assertEquals('$2', $this->formatMoney($position->getCommission()));
        $this->assertEquals(980, $position->getStopLossPrice());
        $this->assertEquals(1020, $position->getTakeProfitPrice());
        $this->assertEquals('$898', $this->formatMoney($position->getAccount()->getAmount()));
    }

    public function testClosePositionByStopLoss(): void
    {
        $tradingSimulator = $this->openPosition();
        $position = $tradingSimulator->getPosition();

        $price = 979;
        $tradingSimulator->updateTrailing($price);
        $tradingSimulator->checkPosition($price);

        $this->assertEquals('-$42', $this->formatMoney($position->getPnl()));
        $this->assertEquals('$4', $this->formatMoney($position->getCommission()));
        $this->assertEquals('$0', $this->formatMoney($position->getAmount()));
        $this->assertEquals(PositionStatusEnum::Closed, $position->getStatus());

        $this->assertEquals('$954', $this->formatMoney($position->getAccount()->getAmount()));
    }

    public function testTrailingStopLoss(): void
    {
        $tradingSimulator = $this->openPosition();
        $position = $tradingSimulator->getPosition();

        $price = 1010;
        $tradingSimulator->updateTrailing($price);
        $tradingSimulator->checkPosition($price);

        $this->assertEquals('990.00', $this->formatFloat($position->getStopLossPrice()));
        $this->assertEquals('1020.00', $this->formatFloat($position->getTakeProfitPrice()));
        $this->assertEquals('$100', $this->formatMoney($position->getAmount()));
        $this->assertEquals(PositionStatusEnum::Open, $position->getStatus());
        $this->assertFalse($position->isClosedPartially());
    }

    public function testTrailingStopLossGoingToBE(): void
    {
        $tradingSimulator = $this->openPosition();
        $position = $tradingSimulator->getPosition();

        $price = 1020;
        $tradingSimulator->updateTrailing($price);
        $tradingSimulator->checkPosition($price);

        $this->assertEquals(PositionStatusEnum::Open, $position->getStatus());
        $this->assertTrue($position->isClosedPartially());
        $this->assertEquals('1000.00', $this->formatFloat($position->getStopLossPrice()));
        $this->assertSame($this->formatFloat($position->getEntryPrice()), $this->formatFloat($position->getStopLossPrice()));
        $this->assertEquals('1022.55', $this->formatFloat($position->getTakeProfitPrice()));
        $this->assertEquals('$30', $this->formatMoney($position->getAmount()));
        $this->assertEquals('$3.40000000', $this->formatMoney($position->getCommission()));
        $this->assertEquals('$28', $this->formatMoney($position->getPnl()));
    }

    public function testClosePartiallyPositionTakeProfit(): void
    {
        $tradingSimulator = $this->openPosition();
        $position = $tradingSimulator->getPosition();

        $price = 1030;
        $tradingSimulator->updateTrailing($price);
        $tradingSimulator->checkPosition($price);

        $this->assertEquals(PositionStatusEnum::Open, $position->getStatus());
        $this->assertTrue($position->isClosedPartially());
        $this->assertEquals('1010.00', $this->formatFloat($position->getStopLossPrice()));
        $this->assertEquals('1032.58', $this->formatFloat($position->getTakeProfitPrice()));
        $this->assertEquals('$30', $this->formatMoney($position->getAmount()));
        $this->assertEquals('$42', $this->formatMoney($position->getPnl()));
        $this->assertEquals('$1,008.60000000', $this->formatMoney($position->getAccount()->getAmount()));
    }

    public function testCloseFullyPositionTakeProfit(): void
    {
        $tradingSimulator = $this->openPosition();
        $position = $tradingSimulator->getPosition();

        $price = 1030;
        $tradingSimulator->updateTrailing($price);
        $tradingSimulator->checkPosition($price);

        $this->assertEquals(PositionStatusEnum::Open, $position->getStatus());
        $this->assertTrue($position->isClosedPartially());
        $this->assertEquals('1010.00', $this->formatFloat($position->getStopLossPrice()));
        $this->assertEquals('1032.58', $this->formatFloat($position->getTakeProfitPrice()));
        $this->assertEquals('$30', $this->formatMoney($position->getAmount()));
        $this->assertEquals('$42', $this->formatMoney($position->getPnl()));
        $this->assertEquals('$1,008.60000000', $this->formatMoney($position->getAccount()->getAmount()));

        $price = 1050;
        $tradingSimulator->updateTrailing($price);
        $tradingSimulator->checkPosition($price);

        $this->assertEquals(PositionStatusEnum::Open, $position->getStatus());
        $this->assertTrue($position->isClosedPartially());
        $this->assertEquals('1040.00', $this->formatFloat($position->getStopLossPrice()));
        $this->assertEquals('1052.63', $this->formatFloat($position->getTakeProfitPrice()));
        $this->assertEquals('$30', $this->formatMoney($position->getAmount()));
        $this->assertEquals('$42', $this->formatMoney($position->getPnl()));
        $this->assertEquals('$1,008.60000000', $this->formatMoney($position->getAccount()->getAmount()));

        $price = 1065;
        $tradingSimulator->updateTrailing($price);
        $tradingSimulator->checkPosition($price);

        $this->assertEquals(PositionStatusEnum::Open, $position->getStatus());
        $this->assertTrue($position->isClosedPartially());
        $this->assertEquals('1050.00', $this->formatFloat($position->getStopLossPrice()));
        $this->assertEquals('1067.66', $this->formatFloat($position->getTakeProfitPrice()));
        $this->assertEquals('$30', $this->formatMoney($position->getAmount()));
        $this->assertEquals('$42', $this->formatMoney($position->getPnl()));
        $this->assertEquals('$1,008.60000000', $this->formatMoney($position->getAccount()->getAmount()));

        $price = 1040;
        $tradingSimulator->updateTrailing($price);
        $tradingSimulator->checkPosition($price);

        $this->assertEquals(PositionStatusEnum::Closed, $position->getStatus());
        $this->assertTrue($position->isClosedPartially());
        $this->assertEquals('1050.00', $this->formatFloat($position->getStopLossPrice()));
        $this->assertEquals('1067.66', $this->formatFloat($position->getTakeProfitPrice()));
        $this->assertEquals('$0', $this->formatMoney($position->getAmount()));
        $this->assertEquals('$66', $this->formatMoney($position->getPnl()));
        $this->assertEquals('$4', $this->formatMoney($position->getCommission()));
        $this->assertEquals('$1,104', $this->formatMoney($position->getAccount()->getAmount()));
    }

    private function openPosition(): TradingSimulator
    {
        $position = $this->createPosition();
        $this->assertEquals(PositionStatusEnum::Ready, $position->getStatus());

        $tradingSimulator = new TradingSimulator($position, $this->eventDispatcher);
        $tradingSimulator->openPosition();

        $this->assertFalse($position->isClosedPartially());

        return $tradingSimulator;
    }

    private function createPosition(): Position
    {
        $position = new Position();
        $position->setAccount($this->createAccount());
        $position->setIntent($this->createIntent());
        $position->setStatus(PositionStatusEnum::Ready);
        $position->setLeverage(20);
        $position->setRisk(0.1);
        $position->setEntryPrice(1000);

        return $position;
    }

    private function createIntent(): Intent
    {
        $intent = new Intent();
        $intent->setTicker($this->createTicker());
        $intent->setExchange(ExchangeEnum::BinanceFutures);
        $intent->setStatus(IntentStatusEnum::Confirmed);
        $intent->setDirection(DirectionEnum::Long);

        return $intent;
    }

    private function createTicker(): Ticker
    {
        $ticker = new Ticker();
        $ticker->setName('SOL');

        return $ticker;
    }

    private function createAccount(): Account
    {
        $account = new Account();
        $account->setExchange(ExchangeEnum::BinanceFutures);
        $account->setAmount(MoneyHelper::createMoney(1000));

        return $account;
    }

    private function formatMoney(Money $money): string
    {
        return MoneyHelper::pretty($money);
    }

    private function formatFloat(float $float): string
    {
        return number_format($float, 2, '.', '');
    }
}
