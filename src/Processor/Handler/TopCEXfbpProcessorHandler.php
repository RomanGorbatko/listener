<?php

namespace App\Processor\Handler;

use App\Enum\ProcessorTypeEnum;

class TopCEXfbpProcessorHandler extends AbstractTopCEXProcessorHandler
{
    public function getType(): ProcessorTypeEnum
    {
        return ProcessorTypeEnum::TopCEXfbp;
    }
}
