<?php

namespace App\Processor\Handler;

use App\Enum\ProcessorTypeEnum;
use App\Processor\AbstractProcessor;

class DTVolumesNewProcessorHandler extends AbstractProcessor
{
    public function processNotification(string $message, \DateTimeImmutable $datetime): void
    {
        $this->processConfirmation($message, $datetime);
    }

    public function getType(): ProcessorTypeEnum
    {
        return ProcessorTypeEnum::DTVolumesNew;
    }
}
