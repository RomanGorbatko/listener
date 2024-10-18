<?php

namespace App\Model\Timestampable;

interface TimestampableInterface
{
    public function setCreatedAt(\DateTimeImmutable $createdAt);

    public function getCreatedAt(): \DateTimeImmutable;

    public function setUpdatedAt(?\DateTimeImmutable $createdAt);

    public function getUpdatedAt(): ?\DateTimeImmutable;

    public function onPreUpdate(): void;
}
