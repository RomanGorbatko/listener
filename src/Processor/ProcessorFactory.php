<?php

namespace App\Processor;

use App\Enum\ProcessorTypeEnum;
use App\Processor\Exception\UndefinedProcessorHandler;

class ProcessorFactory
{
    /**
     * @param AbstractProcessor[] $processors
     */
    public function __construct(protected iterable $processors)
    {
    }

    public function getByProcessorType(ProcessorTypeEnum $processorTypeEnum): AbstractProcessor
    {
        foreach ($this->processors as $processor) {
            if ($processor->getType() === $processorTypeEnum) {
                return $processor;
            }
        }

        throw new UndefinedProcessorHandler($processorTypeEnum);
    }
}
