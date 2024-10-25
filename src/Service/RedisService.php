<?php

namespace App\Service;

use App\Entity\Intent;
use App\Enum\IntentStatusEnum;

readonly class RedisService
{
    private const string TRADES_COST_KEY = 'trades_%s_%s_';

    public function __construct(
        private \Redis $redisDefault,
    ) {
    }

    public function processTradeCostData(Intent $intent): void
    {
        $tickerName = $intent->getTicker()->getName();
        $status = $intent->getStatus();

        $key = sprintf(self::TRADES_COST_KEY, $tickerName, $status->value);

        if ($buyCosts = $this->redisDefault->get($key.'buy')) {
            if (IntentStatusEnum::WaitingForConfirmation === $status) {
                $intent->setConfirmationTradesCostBuy((float) $buyCosts);
            } elseif (IntentStatusEnum::OnPosition === $status) {
                $intent->setOnPositionTradesCostBuy((float) $buyCosts);
            }
        }

        if ($sellCosts = $this->redisDefault->get($key.'sell')) {
            if (IntentStatusEnum::WaitingForConfirmation === $status) {
                $intent->setConfirmationTradesCostSell((float) $sellCosts);
            } elseif (IntentStatusEnum::OnPosition === $status) {
                $intent->setOnPositionTradesCostSell((float) $buyCosts);
            }
        }
    }

    public function clearTradeCostsData(Intent $intent, IntentStatusEnum $status): void
    {
        $tickerName = $intent->getTicker()->getName();

        $this->redisDefault->del(
            sprintf(self::TRADES_COST_KEY, $tickerName, $status->value).'buy',
            sprintf(self::TRADES_COST_KEY, $tickerName, $status->value).'sell'
        );
    }
}
