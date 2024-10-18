<?php

namespace App\Service;

use App\Entity\Currency;
use App\Helper\MoneyHelper;
use App\Repository\CurrencyRepository;
use Brick\Money\CurrencyConverter;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Money;

readonly class CurrencyExchange
{
    public function __construct(
        private CurrencyRepository $currencyRepository,
    ) {
    }

    public function convert(Money $sourceCurrency): Money
    {
        $provider = new ConfigurableProvider();
        /** @var Currency $currency */
        foreach ($this->currencyRepository->findAll() as $currency) {
            $provider->setExchangeRate(
                sourceCurrencyCode: $currency->getCurrency(),
                targetCurrencyCode: Currency::BASE_CURRENCY,
                exchangeRate: $currency->getRate()
            );
        }

        return (new CurrencyConverter($provider))
            ->convert(
                $sourceCurrency, MoneyHelper::getTetherCurrency()
            );
    }
}
