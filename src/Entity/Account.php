<?php

namespace App\Entity;

use App\Enum\ExchangeEnum;
use App\Helper\MoneyHelper;
use App\Model\Timestampable\TimestampableInterface;
use App\Model\Timestampable\TimestampableTrait;
use App\Model\Uuid\UuidInterface;
use App\Model\Uuid\UuidTrait;
use App\Repository\AccountRepository;
use Brick\Money\Money;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[UniqueEntity('exchange')]
#[HasLifecycleCallbacks]
class Account implements UuidInterface, TimestampableInterface
{
    use UuidTrait;
    use TimestampableTrait;

    #[ORM\Column(type: 'string', unique: true, nullable: false, enumType: ExchangeEnum::class)]
    private ExchangeEnum $exchange;

    #[ORM\Column(type: 'bigint', nullable: false)]
    private int $amount;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getExchange(): ExchangeEnum
    {
        return $this->exchange;
    }

    public function setExchange(ExchangeEnum $exchange): void
    {
        $this->exchange = $exchange;
    }

    public function getAmount(): Money
    {
        return MoneyHelper::ofMinorMoney($this->amount);
    }

    public function setAmount(Money $money): void
    {
        $this->amount = $money->getMinorAmount()->toInt();
    }
}
