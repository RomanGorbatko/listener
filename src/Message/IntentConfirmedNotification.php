<?php

namespace App\Message;

readonly final class IntentConfirmedNotification
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
