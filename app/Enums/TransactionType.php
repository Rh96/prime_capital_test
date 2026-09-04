<?php

namespace App\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Withdraw = 'withdraw';
    case Buy = 'buy';
    case Sell = 'sell';

    public function isTrade(): bool
    {
        return $this === self::Buy || $this === self::Sell;
    }

    public function movesCashIn(): bool
    {
        return $this === self::Deposit || $this === self::Sell;
    }
}
