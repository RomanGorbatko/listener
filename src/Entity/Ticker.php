<?php

namespace App\Entity;

use App\Model\Timestampable\TimestampableInterface;
use App\Model\Timestampable\TimestampableTrait;
use App\Model\Uuid\UuidInterface;
use App\Model\Uuid\UuidTrait;
use App\Repository\TickerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TickerRepository::class)]
class Ticker implements UuidInterface, TimestampableInterface
{
    use UuidTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $name;

    #[ORM\Column(type: 'json')]
    private array $exchanges = [];

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getExchanges(): array
    {
        return $this->exchanges;
    }

    public function setExchanges(array $exchanges): void
    {
        $this->exchanges = $exchanges;
    }

    public function pushExchange(string $exchange): void
    {
        if (!in_array($exchange, $this->exchanges, true)) {
            $this->exchanges[] = $exchange;
        }
    }
}
