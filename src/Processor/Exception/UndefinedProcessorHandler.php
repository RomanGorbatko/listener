<?php

namespace App\Processor\Exception;

use App\Enum\ProcessorTypeEnum;

class UndefinedProcessorHandler extends \Exception
{
    public function __construct(private readonly ProcessorTypeEnum $type, string $message = 'Undefined processor handler')
    {
        parent::__construct($message);
    }

    public function getType(): ProcessorTypeEnum
    {
        return $this->type;
    }
}
