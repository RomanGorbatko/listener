<?php

namespace App\Processor\Handler;

use App\Enum\ProcessorTypeEnum;

class TopCEXsbProcessorHandler extends AbstractTopCEXProcessorHandler
{
    public function getType(): ProcessorTypeEnum
    {
        return ProcessorTypeEnum::TopCEXsb;
    }
}
