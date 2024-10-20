<?php

namespace App\Processor\Handler;

use App\Processor\AbstractProcessor;

abstract class AbstractTopCEXProcessorHandler extends AbstractProcessor
{
    public function processNotification(string $message, \DateTimeImmutable $datetime): void
    {
        $this->processConfirmation($message, $datetime);
    }
}
