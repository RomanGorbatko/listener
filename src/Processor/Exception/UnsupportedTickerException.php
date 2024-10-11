<?php

namespace App\Processor\Exception;

class UnsupportedTickerException extends \Exception
{
    public function __construct(private readonly string $ticker)
    {
        parent::__construct('Unsupported Ticker');
    }

    public function getTicker(): string
    {
        return $this->ticker;
    }
}
