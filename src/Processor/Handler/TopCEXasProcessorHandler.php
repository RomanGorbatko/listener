<?php

namespace App\Processor\Handler;

use App\Enum\ProcessorTypeEnum;

class TopCEXasProcessorHandler extends AbstractTopCEXProcessorHandler
{
    public function getType(): ProcessorTypeEnum
    {
        return ProcessorTypeEnum::TopCEXas;
    }
}
