<?php

namespace App\Helper;

use Money\Currencies\CryptoCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Money\MoneyFormatter;
use Money\MoneyParser;
use Money\Parser\DecimalMoneyParser;

class MoneyHelper
{
    public static function parser(): MoneyParser
    {
        return new DecimalMoneyParser(new CryptoCurrencies());
    }

    public static function formater(): MoneyFormatter
    {
        return new DecimalMoneyFormatter(new CryptoCurrencies());
    }
}
