<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientSharesException extends RuntimeException
{
    public function __construct(string $symbol, int $held, int $requested)
    {
        parent::__construct(
            "Insufficient shares of {$symbol}: held {$held}, requested {$requested}."
        );
    }
}
