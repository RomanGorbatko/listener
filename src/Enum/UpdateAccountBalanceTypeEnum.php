<?php

namespace App\Enum;

enum UpdateAccountBalanceTypeEnum: string
{
    case Increase = '+';
    case Decrease = '-';
}
