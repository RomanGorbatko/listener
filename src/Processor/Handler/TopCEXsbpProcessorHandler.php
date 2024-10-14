<?php

namespace App\Processor\Handler;

use App\Enum\ProcessorTypeEnum;

class TopCEXsbpProcessorHandler extends AbstractTopCEXProcessorHandler
{
    public function getType(): ProcessorTypeEnum
    {
        return ProcessorTypeEnum::TopCEXsbp;
    }
}
