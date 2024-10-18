<?php

namespace App\Helper;

use App\Entity\Currency as CurrencyEntity;
use Brick\Money\Currency;
use Brick\Money\Money;

class MoneyHelper
{
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
        return new Currency(currencyCode: CurrencyEntity::BASE_CURRENCY, numericCode: 0, name: 'Tether', defaultFractionDigits: 8);
    }

    public static function pretty(Money $money): string
    {
        return $money->formatTo('en_US', true);
    }
}
