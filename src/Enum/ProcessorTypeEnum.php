<?php

namespace App\Enum;

enum ProcessorTypeEnum: string
{
    use EnumDumperTrait;

    case CEXTrack = 'CEXTrack';
    case TopCEXfb = 'TopCEXfb';
    case TopCEXfbp = 'TopCEXfbp';
    case TopCEXas = 'TopCEXas';
    case TopCEXsbp = 'TopCEXsbp';
    case TopCEXsb = 'TopCEXsb';
    case TopCEXad = 'TopCEXad';
    case DTVolumesNew = 'DTVolumesnew';
    case TVGTrackNew = 'TVGTrackNew';
    case Announcement = 'announcement';
}
