<?php

namespace App\Helper;

use Brick\Money\Currency;
use Brick\Money\Money;

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
        return self::createMoney(0);
    }

    public static function createMoney(int $amount, ?Currency $currency = null): Money
    {
        if (null === $currency) {
            $currency = self::getTetherCurrency();
        }

        return Money::of($amount, $currency);
    }

    public static function ofMinorMoney(int $amount, ?Currency $currency = null): Money
    {
        if (null === $currency) {
            $currency = self::getTetherCurrency();
        }

        return Money::ofMinor($amount, $currency);
    }

    public static function getTetherCurrency(): Currency
    {
        return new Currency(currencyCode: self::BASE_CURRENCY, numericCode: 0, name: 'Tether', defaultFractionDigits: 8);
    }

    public static function pretty(Money $money): string
    {
        return $money->formatTo('en_US', true);
    }
}
