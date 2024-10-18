<?php

namespace App\Processor\Handler;

use App\Entity\Intent;
use App\Entity\Ticker;
use App\Enum\DirectionEnum;
use App\Enum\ExchangeEnum;
use App\Enum\IntentStatusEnum;
use App\Enum\ProcessorTypeEnum;
use App\Event\TelegramLogEvent;
use App\Helper\MoneyHelper;
use App\Processor\AbstractProcessor;
use App\Processor\Exception\FailedExtractElementException;
use App\Processor\Exception\UnsupportedTickerException;
use App\Repository\AccountRepository;
use App\Service\CurrencyExchange;
use App\Trader\TradeManager;
use Brick\Money\Currency;
use Brick\Money\Money;
use Piscibus\PhpHashtag\Extractor;

class CexTrackProcessorHandler extends AbstractProcessor
{
    public function __construct(
        private readonly \Redis $redisDefault,
        private readonly AccountRepository $accountRepository,
        private readonly TradeManager $tradeManager,
        private readonly CurrencyExchange $currencyExchange,
    ) {
    }

    /**
     * @throws FailedExtractElementException|UnsupportedTickerException
     */
    public function processNotification(string $message, \DateTimeImmutable $datetime): void
    {
        if (!$this->tradeManager->isTradeAllowed()) {
            return;
        }

        $lines = explode(PHP_EOL, trim($message));
        if (count($lines) <= 2) {
            throw new FailedExtractElementException('lines');
        }

        $ticker = $this->extractTicker($lines[0]);
        $amount = $this->extractAmount($lines[0]);
        $direction = $this->extractDirection($lines[0]);

        $exchange = $this->extractExchange($lines[0]);
        $volume = $this->extractVolume($lines[2]);

        $tickerEntity = $this->findTickerEntity($ticker, $exchange);
        $this->entityManager->persist($tickerEntity);

        $intentEntity = $this->intentRepository->findOneBy([
            'ticker' => $tickerEntity,
            'status' => [
                IntentStatusEnum::WaitingForConfirmation,
                IntentStatusEnum::Confirmed,
                IntentStatusEnum::OnPosition,
            ],
        ]);

        if (!$amount->getCurrency()->is(MoneyHelper::getTetherCurrency())) {
            $amount = $this->currencyExchange->convert($amount);
        }

        if ($intentEntity instanceof Intent) {
            $modifiedAmount = $intentEntity->getAmount()->plus($amount);
            $intentEntity->setAmount($modifiedAmount);
            $intentEntity->setVolume($volume);

            $messageLog = '❕<b>Intent Updated</b>'.PHP_EOL;
        } else {
            $intentEntity = new Intent();
            $intentEntity->setStatus(IntentStatusEnum::WaitingForConfirmation);
            $intentEntity->setTicker($tickerEntity);
            $intentEntity->setAmount($amount);
            $intentEntity->setDirection($direction);
            $intentEntity->setExchange($exchange);
            $intentEntity->setVolume($volume);
            $intentEntity->setNotifiedAt($datetime);
            $intentEntity->setOriginalMessage($message);

            $messageLog = '❕<b>Intent created</b>'.PHP_EOL;
        }
        $messageLog .= 'Ticker: <i>#'.$intentEntity->getTicker()->getName().'</i>'.PHP_EOL;
        $messageLog .= 'Direction: <i>'.$intentEntity->getDirection()->name.'</i>.PHP_EOL';
        $messageLog .= 'Amount: <i>'.MoneyHelper::pretty($intentEntity->getAmount()).'</i>';
        $this->eventDispatcher->dispatch(new TelegramLogEvent($messageLog));

        $this->entityManager->persist($intentEntity);

        $this->entityManager->flush();
    }

    protected function findTickerEntity(string $ticker, ExchangeEnum $exchange): Ticker
    {
        if (false === $this->isSupportedTicker($ticker)) {
            throw new UnsupportedTickerException($ticker);
        }

        /** @var Ticker|null $tickerEntity */
        $tickerEntity = $this->tickerRepository->findOneBy(['name' => $ticker]);
        if (!$tickerEntity instanceof Ticker) {
            $tickerEntity = new Ticker();
            $tickerEntity->setName($ticker);
            $tickerEntity->setExchanges([$exchange->value]);
        } else {
            $tickerEntity->pushExchange($exchange->value);
        }

        return $tickerEntity;
    }

    protected function isSupportedTicker(string $ticker): bool
    {
        $supportedTicker = false;
        $accountExchanges = $this->accountRepository->getAvailableAccountExchanges();
        foreach ($accountExchanges as $accountExchange) {
            $key = sprintf('%s_%s', $accountExchange->value, 'markets');

            if ($this->redisDefault->sIsMember($key, $ticker)) {
                $supportedTicker = true;

                break;
            }
        }

        return $supportedTicker;
    }

    protected function extractVolume(string $message): int
    {
        preg_match('/: (.*) /', $message, $matches);

        try {
            $volume = $this->parseAmount($matches[1]);

            return (int) $volume;
        } catch (\Throwable $e) {
            throw new FailedExtractElementException('volume', $e->getMessage());
        }
    }

    protected function extractDirection(string $message): DirectionEnum
    {
        if (str_contains($message, 'продают')) {
            return DirectionEnum::Short;
        }

        if (str_contains($message, 'покупают')) {
            return DirectionEnum::Long;
        }

        throw new FailedExtractElementException('direction');
    }

    protected function extractTicker(string $message): string
    {
        try {
            return ltrim(Extractor::extract($message)[0], '#');
        } catch (\Throwable) {
            throw new FailedExtractElementException('ticker');
        }
    }

    protected function extractExchange(string $message): ExchangeEnum
    {
        preg_match('/\(.*\) на (.*)/u', $message, $matches);

        return ExchangeEnum::tryFrom($this->camelCase($matches[1]))
            ?? throw new FailedExtractElementException('exchange')
        ;
    }

    protected function extractAmount(string $message): Money
    {
        preg_match('/на (.*) за/u', $message, $matches);

        if (isset($matches[1])) {
            $lines = explode(' ', trim($matches[1]));

            return MoneyHelper::createMoney(
                (int) $this->parseAmount($lines[0]),
                new Currency($lines[1], 0, '', 8)
            );
        }

        throw new FailedExtractElementException('amount');
    }

    private function parseAmount(string $message): string
    {
        if (str_contains($message, 'K')) {
            return str_replace('K', '000', $message);
        }

        if (str_contains($message, 'M')) {
            return str_replace('M', '000000000', $message);
        }

        return $message;
    }

    private function camelCase($string, bool $capitalizeFirstCharacter = false): string
    {
        $str = str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));

        if (!$capitalizeFirstCharacter) {
            $str[0] = strtolower($str[0]);
        }

        return $str;
    }

    public function getType(): ProcessorTypeEnum
    {
        return ProcessorTypeEnum::CEXTrack;
    }
}
