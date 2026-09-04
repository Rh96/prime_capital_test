<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Exceptions\InsufficientFundsException;
use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    public function deposit(Client $client, string $amount): Transaction
    {
        return DB::transaction(function () use ($client, $amount) {
            $client = Client::query()->lockForUpdate()->find($client->id);

            $balance = bcadd($client->cash_balance, $amount, 4);

            return $this->recordCashMovement($client, TransactionType::Deposit, $amount, $balance);
        });
    }

    public function withdraw(Client $client, string $amount): Transaction
    {
        return DB::transaction(function () use ($client, $amount) {
            $client = Client::query()->lockForUpdate()->find($client->id);

            if (bccomp($client->cash_balance, $amount, 4) < 0) {
                throw new InsufficientFundsException($client->cash_balance, $amount);
            }

            $balance = bcsub($client->cash_balance, $amount, 4);

            return $this->recordCashMovement($client, TransactionType::Withdraw, $amount, $balance);
        });
    }

    private function recordCashMovement(
        Client $client,
        TransactionType $type,
        string $amount,
        string $balance,
    ): Transaction {
        $transaction = $client->transactions()->create([
            'type' => $type,
            'amount' => $amount,
            'symbol' => null,
            'quantity' => null,
            'unit_price' => null,
            'cash_balance_after' => $balance,
            'created_at' => now(),
        ]);

        $client->forceFill(['cash_balance' => $balance])->save();

        return $transaction;
    }
}
