<?php

namespace App\Entity;

use App\Model\Timestampable\TimestampableInterface;
use App\Model\Timestampable\TimestampableTrait;
use App\Model\Uuid\UuidInterface;
use App\Model\Uuid\UuidTrait;
use App\Repository\ConfirmationRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity(repositoryClass: ConfirmationRepository::class)]
#[HasLifecycleCallbacks]
class Confirmation implements UuidInterface, TimestampableInterface
{
    use UuidTrait;
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: Intent::class)]
    #[ORM\JoinColumn(name: 'intent_id', referencedColumnName: 'id', nullable: false)]
    private Intent $intent;

    #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    private \DateTimeImmutable $notifiedAt;

    #[ORM\Column(type: 'text', nullable: false)]
    private string $originalMessage;

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
}
