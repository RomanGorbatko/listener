<?php

namespace App\Entity;

use App\Enum\DirectionEnum;
use App\Enum\ExchangeEnum;
use App\Enum\IntentStatusEnum;
use App\Helper\MoneyHelper;
use App\Model\Timestampable\TimestampableInterface;
use App\Model\Timestampable\TimestampableTrait;
use App\Model\Uuid\UuidInterface;
use App\Model\Uuid\UuidTrait;
use App\Repository\IntentRepository;
use Brick\Money\Money;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IntentRepository::class)]
class Intent implements UuidInterface, TimestampableInterface
{
    use UuidTrait;
    use TimestampableTrait;

    #[ORM\Column(type: 'string', nullable: false, enumType: ExchangeEnum::class)]
    private ExchangeEnum $exchange;

    #[ORM\Column(type: 'string', nullable: false, enumType: DirectionEnum::class)]
    private DirectionEnum $direction;

    #[ORM\Column(type: 'string', nullable: false, enumType: IntentStatusEnum::class)]
    private IntentStatusEnum $status;

    #[ORM\ManyToOne(targetEntity: Ticker::class)]
    #[ORM\JoinColumn(name: 'ticker_id', referencedColumnName: 'id', nullable: false)]
    private Ticker $ticker;

    #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    private \DateTimeImmutable $notifiedAt;

    #[ORM\Column(type: 'text', nullable: false)]
    private string $originalMessage;

    #[ORM\Column(type: 'bigint', nullable: false)]
    private int $volume;

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

    public function getDirection(): DirectionEnum
    {
        return $this->direction;
    }

    public function setDirection(DirectionEnum $direction): void
    {
        $this->direction = $direction;
    }

    public function getNotifiedAt(): \DateTimeImmutable
    {
        return $this->notifiedAt;
    }

    public function setNotifiedAt(\DateTimeImmutable $notifiedAt): void
    {
        $this->notifiedAt = $notifiedAt;
    }

    public function getOriginalMessage(): string
    {
        return $this->originalMessage;
    }

    public function setOriginalMessage(string $originalMessage): void
    {
        $this->originalMessage = $originalMessage;
    }

    public function getVolume(): int
    {
        return $this->volume;
    }

    public function setVolume(int $volume): void
    {
        $this->volume = $volume;
    }

    public function getAmount(): Money
    {
        return MoneyHelper::ofMinorMoney($this->amount);
    }

    public function setAmount(Money $money): void
    {
        $this->amount = $money->getMinorAmount()->toInt();
    }

    public function getTicker(): Ticker
    {
        return $this->ticker;
    }

    public function setTicker(Ticker $ticker): void
    {
        $this->ticker = $ticker;
    }

    public function getStatus(): IntentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(IntentStatusEnum $status): void
    {
        $this->status = $status;
    }
}
