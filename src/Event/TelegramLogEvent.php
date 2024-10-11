<?php

namespace App\Event;

use App\Enum\TelegramLogLevelEnum;
use Symfony\Contracts\EventDispatcher\Event;

class TelegramLogEvent extends Event
{
    private const TELEGRAM_MESSAGE_MAX_LENGTH = 4096;

    public function __construct(
        private readonly string $message,
        private readonly TelegramLogLevelEnum $level = TelegramLogLevelEnum::Info,
    ) {
    }

    public function getMessage(): string
    {
        return substr($this->message, 0, self::TELEGRAM_MESSAGE_MAX_LENGTH);
    }

    public function getLevel(): TelegramLogLevelEnum
    {
        return $this->level;
    }
}
