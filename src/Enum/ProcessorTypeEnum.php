<?php

namespace App\Enum;

enum ProcessorTypeEnum: string
{
    use EnumDumperTrait;

    case CEXTrack = 'CEXTrack';
    case TopCEXfb = 'TopCEXfb';
    case TopCEXfbp = 'TopCEXfbp';
}
