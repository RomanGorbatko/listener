<?php

namespace App\Command\Account;

use App\Entity\Account;
use App\Helper\MoneyHelper;
use App\Repository\AccountRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'account:status',
)]
class AccountStatusCommand extends Command
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $table = $io->createTable();
        $table->setHeaders(['Exchange', 'Amount']);

        /** @var Account[] $accounts */
        $accounts = $this->accountRepository->findAll();
        foreach ($accounts as $account) {
            $table->addRow([
                $account->getExchange()->value,
                MoneyHelper::pretty($account->getAmount()),
            ]);
        }

        $table->setVertical();
        $table->render();

        return Command::SUCCESS;
    }
}
