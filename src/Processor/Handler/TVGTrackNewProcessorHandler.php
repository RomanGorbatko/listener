<?php

namespace App\Processor\Handler;

use App\Enum\ProcessorTypeEnum;
use App\Processor\AbstractProcessor;

class TVGTrackNewProcessorHandler extends AbstractProcessor
{
    public function processNotification(string $message, \DateTimeImmutable $datetime): void
    {
        $this->processConfirmation($message, $datetime);
    }

    public function getType(): ProcessorTypeEnum
    {
        return ProcessorTypeEnum::TVGTrackNew;
    }
}
