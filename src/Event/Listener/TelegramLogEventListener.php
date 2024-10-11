<?php

namespace App\Event\Listener;

use App\Event\TelegramLogEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Notifier\Bridge\Telegram\Reply\Markup\InlineKeyboardMarkup;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;

#[AsEventListener(event: TelegramLogEvent::class)]
readonly class TelegramLogEventListener
{
    public function __construct(
        private ChatterInterface $chatter
    ) {
    }

    public function __invoke(TelegramLogEvent $event): void
    {
        $telegramOptions = (new TelegramOptions())
            ->parseMode(TelegramOptions::PARSE_MODE_HTML);

        $chatMessage = new ChatMessage($event->getMessage());
        $chatMessage->options($telegramOptions);

        $this->chatter->send($chatMessage);
    }
}
