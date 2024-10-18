<?php

namespace App\Event\Listener;

use App\Event\TelegramLogEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;

#[AsEventListener(event: TelegramLogEvent::class)]
readonly class TelegramLogEventListener
{
    public function __construct(
        private ChatterInterface $chatter,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(TelegramLogEvent $event): void
    {
        $telegramOptions = (new TelegramOptions())
            ->parseMode(TelegramOptions::PARSE_MODE_HTML);

        $chatMessage = new ChatMessage($event->getMessage());
        $chatMessage->options($telegramOptions);

        try {
            $this->chatter->send($chatMessage);
        } catch (\Throwable $exception) {
            $this->logger->error(self::class, [
                'message' => $exception->getMessage(),
                'event' => $event->getMessage(),
            ]);
        }
    }
}
