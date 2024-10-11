<?php

namespace App\Enum;

enum DirectionEnum: string
{
    use EnumDumperTrait;

    case Long = 'long';
    case Short = 'short';
}
