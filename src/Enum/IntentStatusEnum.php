<?php

namespace App\Enum;

enum IntentStatusEnum: string
{
    case WaitingForConfirmation = 'waiting_for_confirmation';
    case Confirmed = 'confirmed';
    case Expired = 'expired';
    case OnPosition = 'on_position';
    case Closed = 'closed';
}
