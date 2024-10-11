<?php

namespace App\Processor\Exception;

class FailedExtractElementException extends \Exception
{
    public function __construct(private readonly string $type, string $message = 'Extraction failed')
    {
        parent::__construct($message);
    }

    public function getType(): string
    {
        return $this->type;
    }
}
