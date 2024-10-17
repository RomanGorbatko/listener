<?php

namespace App\Entity;

use App\Enum\PositionStatusEnum;
use App\Helper\MoneyHelper;
use App\Model\Timestampable\TimestampableInterface;
use App\Model\Timestampable\TimestampableTrait;
use App\Model\Uuid\UuidInterface;
use App\Model\Uuid\UuidTrait;
use App\Repository\PositionRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;

#[ORM\Entity(repositoryClass: PositionRepository::class)]
class Position implements UuidInterface, TimestampableInterface
{
    use UuidTrait;
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: Intent::class)]
    #[ORM\JoinColumn(name: 'intent_id', referencedColumnName: 'id', nullable: false)]
    private Intent $intent;

    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(name: 'account_id', referencedColumnName: 'id', nullable: false)]
    private Account $account;

    #[ORM\Column(type: 'string', nullable: false, enumType: PositionStatusEnum::class)]
    private PositionStatusEnum $status;

    #[ORM\Column(type: 'float', nullable: false)]
    private float $entryPrice;

    #[ORM\Column(type: 'float', nullable: true)]
    private float|null $stopLossPrice = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private float|null $takeProfitPrice = null;

    #[ORM\Column(type: 'float', nullable: false)]
    private float $risk;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $leverage;

    #[ORM\Column(type: 'string', nullable: true)]
    private string|null $amount = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private string|null $pnl = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private string|null $commission = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $closedPartially = false;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getIntent(): Intent
    {
        return $this->intent;
    }

    public function setIntent(Intent $intent): void
    {
        $this->intent = $intent;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function setAccount(Account $account): void
    {
        $this->account = $account;
    }

    public function getEntryPrice(): float
    {
        return $this->entryPrice;
    }

    public function setEntryPrice(float $entryPrice): void
    {
        $this->entryPrice = $entryPrice;
    }

    public function getStopLossPrice(): ?float
    {
        return $this->stopLossPrice;
    }

    public function setStopLossPrice(?float $stopLossPrice): void
    {
        $this->stopLossPrice = $stopLossPrice;
    }

    public function getTakeProfitPrice(): ?float
    {
        return $this->takeProfitPrice;
    }

    public function setTakeProfitPrice(?float $takeProfitPrice): void
    {
        $this->takeProfitPrice = $takeProfitPrice;
    }

    public function getStatus(): PositionStatusEnum
    {
        return $this->status;
    }

    public function setStatus(PositionStatusEnum $status): void
    {
        $this->status = $status;
    }

    public function getRisk(): float
    {
        return $this->risk;
    }

    public function setRisk(float $risk): void
    {
        $this->risk = $risk;
    }

    public function getLeverage(): int
    {
        return $this->leverage;
    }

    public function setLeverage(int $leverage): void
    {
        $this->leverage = $leverage;
    }

    public function getAmount(): ?Money
    {
        if ($this->amount === null) {
            return null;
        }

        return new Money($this->amount, new Currency(MoneyHelper::BASE_CURRENCY));
    }

    public function setAmount(Money $money): void
    {
        $this->amount = $money->getAmount();
    }

    public function getPnl(): ?Money
    {
        if ($this->pnl === null) {
            return null;
        }

        return new Money($this->pnl, new Currency(MoneyHelper::BASE_CURRENCY));
    }

    public function setPnl(Money $pnl): void
    {
        $this->pnl = $pnl->getAmount();
    }

    public function getCommission(): ?Money
    {
        if ($this->commission === null) {
            return null;
        }

        return new Money($this->commission, new Currency(MoneyHelper::BASE_CURRENCY));
    }

    public function setCommission(Money $commission): void
    {
        $this->commission = $commission->getAmount();
    }

    public function isClosedPartially(): bool
    {
        return $this->closedPartially;
    }

    public function setClosedPartially(bool $closedPartially): void
    {
        $this->closedPartially = $closedPartially;
    }
}
