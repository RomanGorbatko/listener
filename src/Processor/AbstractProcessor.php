<?php

namespace App\Processor;

use App\Enum\ProcessorTypeEnum;
use App\Repository\ConfirmationRepository;
use App\Repository\IntentRepository;
use App\Repository\TickerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractProcessor implements ProcessorInterface
{
    protected IntentRepository $intentRepository;
    protected TickerRepository $tickerRepository;
    protected ConfirmationRepository $confirmationRepository;
    protected EntityManagerInterface $entityManager;

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

    abstract public function processNotification(string $message, \DateTimeImmutable $datetime): void;
    abstract public function getType(): ProcessorTypeEnum;
}
