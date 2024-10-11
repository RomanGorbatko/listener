<?php

namespace App\Repository;

use App\Entity\Account;
use App\Enum\ExchangeEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    /**
     * @return ExchangeEnum[]
     */
    public function getAvailableAccountExchanges(): array
    {
        $result = [];
        /** @var Account[] $accounts */
        $accounts = $this->findAll();
        foreach ($accounts as $account) {
            $result[] = $account->getExchange();
        }

        return $result;
    }
}
