<?php

namespace App\Repository;

use App\Entity\Account;
use App\Enum\ExchangeEnum;
use App\Enum\UpdateAccountBalanceTypeEnum;
use Brick\Money\Money;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly LoggerInterface $logger)
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

    public function updateBalance(Account $account, Money $money, UpdateAccountBalanceTypeEnum $updateType): void
    {
        $query = $this->getEntityManager()
            ->createQuery(
                "UPDATE App\Entity\Account a 
                   SET a.amount = a.amount ".$updateType->value.' ?0
                   WHERE a.id = ?1'
            )
        ;

        $this->logger->info(self::class.'::increaseBalance', [
            'sql' => $query->getSQL(),
            'money' => (string) $money,
            'account' => (string) $account->getId(),
        ]);

        $query->execute([
            $money->getMinorAmount()->toInt(),
            $account->getId(),
        ]);
    }

    public function getMinorBalance(Account $account): int
    {
        $query = $this->getEntityManager()
            ->createQuery("SELECT a.amount FROM App\Entity\Account a WHERE a.id = ?0")
            ->setParameter('0', $account->getId())
        ;

        return $query->getSingleScalarResult();
    }
}
