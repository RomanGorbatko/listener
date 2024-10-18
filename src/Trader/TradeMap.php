<?php

namespace App\Trader;

class TradeMap
{
    private array $map = [];

    public function setTrade(string $symbol, TradingSimulator $tradingSimulator): void
    {
        $this->map[$symbol] = $tradingSimulator;
    }

    public function getTrade(string $symbol): ?TradingSimulator
    {
        return $this->map[$symbol] ?? null;
    }

    public function removeTrade(string $symbol): void
    {
        $this->map[$symbol] = null;
        unset($this->map[$symbol]);
    }
}
