<?php

namespace App\Command\Account;

use App\Entity\Account;
use App\Enum\ExchangeEnum;
use App\Helper\MoneyHelper;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'account:register',
)]
class AccountRegisterCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AccountRepository $accountRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('exchange', InputArgument::REQUIRED, 'Exchange value', null, ExchangeEnum::getValues())
            ->addArgument('amount', InputArgument::REQUIRED, 'Amount value')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $exchangeArgument = $input->getArgument('exchange');
        $amountArgument = $input->getArgument('amount');

        $exchange = ExchangeEnum::from($exchangeArgument);
        $amount = MoneyHelper::createMoney($amountArgument);

        /** @var Account|null $accountEntity */
        $accountEntity = $this->accountRepository->findOneBy(['exchange' => $exchange]);
        if ($accountEntity instanceof Account) {
            $io->warning(sprintf('Account for %s exchange already exists', $accountEntity->getExchange()->value));

            return Command::INVALID;
        }

        $accountEntity = new Account();
        $accountEntity->setExchange($exchange);
        $accountEntity->setAmount($amount);

        $this->entityManager->persist($accountEntity);
        $this->entityManager->flush();

        $io->success('Register successfully');
        $table = $io->createTable();
        $table->setHeaders(['Exchange', 'Amount']);
        $table->addRow([$exchange->value, MoneyHelper::pretty($amount)]);
        $table->setVertical();

        $table->render();

        return Command::SUCCESS;
    }
}
