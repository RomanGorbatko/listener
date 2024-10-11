<?php

namespace App\Message;

use DateTimeImmutable;

readonly final class CryptoAttackNotification
{
    public function __construct(
        private string $content,
        private DateTimeImmutable $datetime,
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getDatetime(): DateTimeImmutable
    {
        return $this->datetime;
    }
}
