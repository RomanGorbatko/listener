<?php

namespace App\Command;

use App\Entity\Intent;
use App\Enum\IntentStatusEnum;
use App\Event\TelegramLogEvent;
use App\Repository\IntentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'app:update-expired-intents',
)]
class UpdateExpiredIntentsCommand extends Command
{
    private const EXPIRE_AFTER_SECONDS = 7200; // 2h

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly IntentRepository $intentRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new \DateTimeImmutable();

        /** @var Intent[] $intents */
        $intents = $this->intentRepository->findBy(['status' => IntentStatusEnum::WaitingForConfirmation]);
        foreach ($intents as $intent) {
            if (($now->getTimestamp() - $intent->getCreatedAt()->getTimestamp()) >= self::EXPIRE_AFTER_SECONDS) {
                $intent->setStatus(IntentStatusEnum::Expired);

                $this->entityManager->persist($intent);

                $logMessage = '❕️<b>Intent expired</b>'.PHP_EOL;
                $logMessage .= 'Ticker: <i>#'.$intent->getTicker()->getName().'</i>';
                $this->eventDispatcher->dispatch(new TelegramLogEvent($logMessage));
            }
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
