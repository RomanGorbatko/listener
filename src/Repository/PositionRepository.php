<?php

namespace App\Repository;

use App\Entity\Position;
use App\Entity\Ticker;
use App\Enum\ExchangeEnum;
use App\Enum\PositionStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Position::class);
    }

    public function isOpenedPositionExists(Ticker $ticker, ExchangeEnum $exchange): bool
    {
        $qb = $this->createQueryBuilder('position');
        $qb
            ->leftJoin('position.intent', 'intent')
            ->leftJoin('position.account', 'account')
            ->andWhere(
                $qb->expr()->eq('intent.ticker', ':ticker')
            )
            ->andWhere(
                $qb->expr()->eq('account.exchange', ':exchange')
            )
            ->andWhere(
                $qb->expr()->eq('position.status', ':status')
            )
            ->setParameter('ticker', $ticker)
            ->setParameter('exchange', $exchange->value)
            ->setParameter('status', PositionStatusEnum::Open->value)
        ;

        return !($qb->getQuery()->getOneOrNullResult() === null);
    }
}
