<?php

namespace App\Model\Timestampable;

interface TimestampableInterface
{
    public function setCreatedAt(\DateTimeImmutable $createdAt);

    public function getCreatedAt(): \DateTimeImmutable;
}
