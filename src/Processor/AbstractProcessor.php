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
use App\Repository\ConfirmationRepository;
use App\Repository\IntentRepository;
use App\Repository\TickerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Piscibus\PhpHashtag\Extractor;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractProcessor implements ProcessorInterface
{
    protected IntentRepository $intentRepository;
    protected TickerRepository $tickerRepository;
    protected ConfirmationRepository $confirmationRepository;
    protected EntityManagerInterface $entityManager;
    protected EventDispatcherInterface $eventDispatcher;
    protected MessageBusInterface $messageBus;

    abstract public function processNotification(string $message, \DateTimeImmutable $datetime): void;

    abstract public function getType(): ProcessorTypeEnum;

    protected function processConfirmation(string $message, \DateTimeImmutable $datetime): void
    {
        $tickers = $this->extractTickers($message);
        $tickersEntities = $this->tickerRepository->findBy(['name' => $tickers]);

        $tickerEntityIds = [];
        foreach ($tickers as $ticker) {
            $tickerEntity = current(array_filter($tickersEntities, static function (Ticker $tickerEntity) use ($ticker) {
                return $tickerEntity->getName() === $ticker;
            }));

            if ($tickerEntity instanceof Ticker) {
                $tickerEntity->pushExchange(ExchangeEnum::BinanceFutures->value);
            } else {
                $tickerEntity = new Ticker();
                $tickerEntity->setName($ticker);
                $tickerEntity->setExchanges([ExchangeEnum::BinanceFutures->value]);
            }

            $this->entityManager->persist($tickerEntity);

            $tickerEntityIds[] = $tickerEntity->getId();
        }

        $this->entityManager->flush();

        /** @var Intent[] $intents */
        $intents = $this->intentRepository->findBy([
            'ticker' => $tickerEntityIds,
            'status' => [
                IntentStatusEnum::WaitingForConfirmation,
                IntentStatusEnum::Confirmed,
                IntentStatusEnum::OnPosition,
            ],
        ]);

        foreach ($intents as $intent) {
            $confirmationEntity = new Confirmation();
            $confirmationEntity->setIntent($intent);
            $confirmationEntity->setOriginalMessage($message);
            $confirmationEntity->setNotifiedAt($datetime);

            $this->entityManager->persist($confirmationEntity);
            $this->entityManager->flush();

            if (IntentStatusEnum::OnPosition !== $intent->getStatus()) {
                $logMessage = '⚠️ <b>Confirmation received</b>'.PHP_EOL;
                $logMessage .= 'Ticker: <i>#'.$confirmationEntity->getIntent()->getTicker()->getName().'</i>'.PHP_EOL;
                $logMessage .= 'Direction: <i>'.$confirmationEntity->getIntent()->getDirection()->name.'</i>';
                $this->eventDispatcher->dispatch(new TelegramLogEvent($logMessage));
            }

            $this->messageBus->dispatch(
                new IntentConfirmedNotification($confirmationEntity->getId())
            );
        }
    }

    /**
     * @return string[]
     */
    protected function extractTickers(string $message): array
    {
        $hashtags = Extractor::extract($message);
        $hashtags = array_values(array_filter($hashtags, fn ($item) => $item !== '#'.$this->getType()->value));

        return array_unique(array_map(static fn ($item) => ltrim($item, '#'), $hashtags));
    }

    #[Required]
    public function setIntentRepository(IntentRepository $intentRepository): void
    {
        $this->intentRepository = $intentRepository;
    }

    #[Required]
    public function setTickerRepository(TickerRepository $tickerRepository): void
    {
        $this->tickerRepository = $tickerRepository;
    }

    #[Required]
    public function setConfirmationRepository(ConfirmationRepository $confirmationRepository): void
    {
        $this->confirmationRepository = $confirmationRepository;
    }

    #[Required]
    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    #[Required]
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    #[Required]
    public function setMessageBus(MessageBusInterface $messageBus): void
    {
        $this->messageBus = $messageBus;
    }
}
