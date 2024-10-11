<?php

namespace App\Enum;

trait EnumDumperTrait
{
    /** @return array<string|int> */
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
