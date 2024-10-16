<?php

namespace App\Enum;

enum ExchangeEnum: string
{
    use EnumDumperTrait;

    case Simulator = 'simulator';
    case Binance = 'binance';
    case BinanceFutures = 'binanceFutures';
    case Bybit = 'bybit';
    case BybitFutures = 'bybitFutures';
    case Gate = 'gate';
    case Kucoin = 'kucoin';
    case Bitfinex = 'bitfinex';
    case BitfinexFutures = 'bitfinexFutures';
    case Coinbase = 'coinbase';
    case Htx = 'htx';
}
