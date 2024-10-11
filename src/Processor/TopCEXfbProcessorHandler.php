<?php

namespace App\Processor;

use App\Entity\Confirmation;
use App\Entity\Intent;
use App\Entity\Ticker;
use App\Enum\ExchangeEnum;
use App\Enum\IntentStatusEnum;
use App\Enum\ProcessorTypeEnum;
use App\Event\TelegramLogEvent;
use App\Message\IntentConfirmedNotification;
use Piscibus\PhpHashtag\Extractor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class TopCEXfbProcessorHandler extends AbstractProcessor
{
    private const HASHTAG = '#TopCEXfb';
    private const EXCHANGE = ExchangeEnum::BinanceFutures;

    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function processNotification(string $message, \DateTimeImmutable $datetime): void
    {
        $tickers = $this->extractTickers($message);
        $tickersEntities = $this->tickerRepository->findBy(['name' => $tickers]);

        $tickerEntityIds = [];
        foreach ($tickers as $ticker) {
            $tickerEntity = current(array_filter($tickersEntities, static function (Ticker $tickerEntity) use ($ticker) {
                return $tickerEntity->getName() === $ticker;
            }));

            if ($tickerEntity instanceof Ticker) {
                $tickerEntity->pushExchange(self::EXCHANGE->value);
            } else {
                $tickerEntity = new Ticker();
                $tickerEntity->setName($ticker);
                $tickerEntity->setExchanges([self::EXCHANGE->value]);
            }

            $this->entityManager->persist($tickerEntity);

            $tickerEntityIds[] = $tickerEntity->getId();
        }

        /** @var Intent[] $intents */
        $intents = $this->intentRepository->findBy([
            'ticker' => $tickerEntityIds,
            'status' => IntentStatusEnum::WaitingForConfirmation,
        ]);

        $this->entityManager->flush();

        foreach ($intents as $intent) {
            $confirmationEntity = new Confirmation();
            $confirmationEntity->setIntent($intent);
            $confirmationEntity->setOriginalMessage($message);
            $confirmationEntity->setNotifiedAt($datetime);

            $this->entityManager->persist($confirmationEntity);
            $this->entityManager->flush();

            $message = '❕<b>Confirmation received</b>' . PHP_EOL;
            $message .= 'Ticker: <i>' . $confirmationEntity->getIntent()->getTicker()->getName() . '</i>' . PHP_EOL;
            $message .= 'Direction: <i>' . $confirmationEntity->getIntent()->getDirection()->name . '</i>';
            $this->eventDispatcher->dispatch(new TelegramLogEvent($message));

            $this->bus->dispatch(
                new IntentConfirmedNotification($confirmationEntity->getId())
            );
        }
    }

    public function getType(): ProcessorTypeEnum
    {
        return ProcessorTypeEnum::TopCEXfb;
    }

    /**
     * @return string[]
     */
    private function extractTickers(string $message): array
    {
        $hashtags = Extractor::extract($message);
        $hashtags = array_values(array_filter($hashtags, static fn ($item) => $item !== self::HASHTAG));

        return array_map(static fn ($item) => ltrim($item, '#'), $hashtags);
    }
}
