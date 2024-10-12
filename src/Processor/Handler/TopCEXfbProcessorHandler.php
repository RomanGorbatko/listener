<?php

namespace App\Processor\Handler;

use App\Enum\ProcessorTypeEnum;

class TopCEXfbProcessorHandler extends AbstractTopCEXProcessorHandler
{
    public function getType(): ProcessorTypeEnum
    {
        return ProcessorTypeEnum::TopCEXfb;
    }
}
