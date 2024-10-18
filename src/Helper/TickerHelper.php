<?php

namespace App\Helper;

class TickerHelper
{
    public static function tickerToSymbol(string $ticker): string
    {
        return $ticker.'/USDT:USDT';
    }

    public static function symbolToTicker(string $symbol): string
    {
        return str_replace('/USDT:USDT', '', $symbol);
    }
}
