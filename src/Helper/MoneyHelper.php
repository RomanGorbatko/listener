<?php

namespace App\Helper;

use Money\Currencies\CryptoCurrencies;
use Money\Currency;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;
use Money\MoneyFormatter;
use Money\MoneyParser;
use Money\Parser\DecimalMoneyParser;

class MoneyHelper
{
    public const string BASE_CURRENCY = 'USDT';

    public static function parser(): MoneyParser
    {
        return new DecimalMoneyParser(new CryptoCurrencies());
    }

    public static function formater(): MoneyFormatter
    {
        return new DecimalMoneyFormatter(new CryptoCurrencies());
    }

    public static function createZeroMoney(): Money
    {
        return new Money('0', new Currency(self::BASE_CURRENCY));
    }
}
