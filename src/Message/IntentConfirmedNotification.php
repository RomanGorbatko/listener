<?php

namespace App\Message;

final readonly class IntentConfirmedNotification
{
    public function __construct(
        private string $confirmationId,
    ) {
    }

    public function getConfirmationId(): string
    {
        return $this->confirmationId;
    }
}
