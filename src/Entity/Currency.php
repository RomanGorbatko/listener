<?php

namespace App\Entity;

use App\Model\Timestampable\TimestampableInterface;
use App\Model\Timestampable\TimestampableTrait;
use App\Model\Uuid\UuidInterface;
use App\Model\Uuid\UuidTrait;
use App\Repository\CurrencyRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity(repositoryClass: CurrencyRepository::class)]
#[HasLifecycleCallbacks]
class Currency implements UuidInterface, TimestampableInterface
{
    use UuidTrait;
    use TimestampableTrait;

    public const string BASE_CURRENCY = 'USDT';

    public const array ALLOWED_CURRENCIES = [
        'BTC', 'ETH', 'BNB', 'USDC',
    ];

    public const array STABLES = [
        'USDC',
    ];

    #[ORM\Column(type: 'string')]
    private string $currency;

    #[ORM\Column(type: 'float')]
    private float $rate;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function setRate(float $rate): void
    {
        $this->rate = $rate;
    }
}
