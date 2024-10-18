<?php

namespace App\Entity;

use App\Enum\PositionStatusEnum;
use App\Helper\MoneyHelper;
use App\Model\Timestampable\TimestampableInterface;
use App\Model\Timestampable\TimestampableTrait;
use App\Model\Uuid\UuidInterface;
use App\Model\Uuid\UuidTrait;
use App\Repository\PositionRepository;
use Brick\Money\Money;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity(repositoryClass: PositionRepository::class)]
#[HasLifecycleCallbacks]
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
    private ?float $stopLossPrice = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $takeProfitPrice = null;

    #[ORM\Column(type: 'float', nullable: false)]
    private float $risk;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $leverage;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $amount = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $pnl = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $commission = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $closedPartially = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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
        if (null === $this->amount) {
            return null;
        }

        return MoneyHelper::ofMinorMoney($this->amount);
    }

    public function setAmount(Money $money): void
    {
        $this->amount = $money->getMinorAmount()->toInt();
    }

    public function getPnl(): ?Money
    {
        if (null === $this->pnl) {
            return null;
        }

        return MoneyHelper::ofMinorMoney($this->pnl);
    }

    public function setPnl(Money $money): void
    {
        $this->pnl = $money->getMinorAmount()->toInt();
    }

    public function getCommission(): ?Money
    {
        if (null === $this->commission) {
            return null;
        }

        return MoneyHelper::ofMinorMoney($this->commission);
    }

    public function setCommission(Money $money): void
    {
        $this->commission = $money->getMinorAmount()->toInt();
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
