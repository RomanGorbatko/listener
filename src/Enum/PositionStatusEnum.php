<?php

namespace App\Enum;

enum PositionStatusEnum: string
{
    use EnumDumperTrait;

    case Ready = 'ready';
    case Open = 'open';
    case Closed = 'closed';
}
