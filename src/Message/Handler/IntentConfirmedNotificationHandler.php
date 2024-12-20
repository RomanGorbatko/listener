<?php

namespace App\Message\Handler;

use App\Entity\Account;
use App\Entity\Confirmation;
use App\Entity\Intent;
use App\Enum\ExchangeEnum;
use App\Enum\IntentStatusEnum;
use App\Message\IntentConfirmedNotification;
use App\Repository\AccountRepository;
use App\Repository\ConfirmationRepository;
use App\Service\RedisService;
use App\Trader\TradeManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class IntentConfirmedNotificationHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ConfirmationRepository $confirmationRepository,
        private AccountRepository $accountRepository,
        private TradeManager $tradeManager,
        private RedisService $redisService
    ) {
    }

    public function __invoke(IntentConfirmedNotification $message): void
    {
        /** @var Confirmation|null $confirmationEntity */
        $confirmationEntity = $this->confirmationRepository->find($message->getConfirmationId());

        if (
            $confirmationEntity instanceof Confirmation
            && IntentStatusEnum::WaitingForConfirmation === $confirmationEntity->getIntent()->getStatus()
        ) {
            $intentEntity = $confirmationEntity->getIntent();

            $this->redisService->processTradeCostData($intentEntity);

            $intentEntity->setStatus(IntentStatusEnum::Confirmed);

            $this->entityManager->persist($confirmationEntity);
            $this->entityManager->flush();

            $this->tradeManager->openPosition($intentEntity, $this->getAccount());

            $this->redisService->clearTradeCostsData($intentEntity, IntentStatusEnum::WaitingForConfirmation);
        }
    }

    private function getAccount(): Account
    {
        return $this->accountRepository->findOneBy(['exchange' => ExchangeEnum::BinanceFutures->value]);
    }
}
