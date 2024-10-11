<?php

namespace App\Entity;

use App\Enum\ExchangeEnum;
use App\Model\Timestampable\TimestampableInterface;
use App\Model\Timestampable\TimestampableTrait;
use App\Model\Uuid\UuidInterface;
use App\Model\Uuid\UuidTrait;
use App\Repository\AccountRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[UniqueEntity('exchange')]
class Account implements UuidInterface, TimestampableInterface
{
    use UuidTrait;
    use TimestampableTrait;

    #[ORM\Column(type: 'string', unique: true, nullable: false, enumType: ExchangeEnum::class)]
    private ExchangeEnum $exchange;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $amount;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $currency;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
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
        return new Money($this->amount, new Currency($this->currency));
    }

    public function setAmount(Money $money): void
    {
        $this->amount = $money->getAmount();
        $this->currency = $money->getCurrency()->getCode();
    }
}
