<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientFundsException extends RuntimeException
{
    public function __construct(string $available, string $requested)
    {
        parent::__construct(
            "Insufficient funds: available {$available}, requested {$requested}."
        );
    }
}
