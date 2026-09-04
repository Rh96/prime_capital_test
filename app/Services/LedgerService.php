<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InsufficientSharesException;
use App\Models\Client;
use App\Models\Holding;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    public function deposit(Client $client, string $amount): Transaction
    {
        return DB::transaction(function () use ($client, $amount) {
            $client = Client::query()->lockForUpdate()->find($client->id);

            $balance = bcadd($client->cash_balance, $amount, 4);

            return $this->record($client, TransactionType::Deposit, $amount, $balance);
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

            return $this->record($client, TransactionType::Withdraw, $amount, $balance);
        });
    }

    public function buy(Client $client, string $symbol, int $quantity, string $unitPrice): Transaction
    {
        return DB::transaction(function () use ($client, $symbol, $quantity, $unitPrice) {
            $client = Client::query()->lockForUpdate()->find($client->id);

            $symbol = strtoupper(trim($symbol));
            $amount = bcmul((string) $quantity, $unitPrice, 4);

            if (bccomp($client->cash_balance, $amount, 4) < 0) {
                throw new InsufficientFundsException($client->cash_balance, $amount);
            }

            $balance = bcsub($client->cash_balance, $amount, 4);
            $transaction = $this->record(
                $client,
                TransactionType::Buy,
                $amount,
                $balance,
                $symbol,
                $quantity,
                $unitPrice,
            );

            $this->upsertHolding($client, $symbol, $quantity);

            return $transaction;
        });
    }

    public function sell(Client $client, string $symbol, int $quantity, string $unitPrice): Transaction
    {
        return DB::transaction(function () use ($client, $symbol, $quantity, $unitPrice) {
            $client = Client::query()->lockForUpdate()->find($client->id);

            $symbol = strtoupper(trim($symbol));

            $holding = Holding::query()
                ->where('client_id', $client->id)
                ->where('symbol', $symbol)
                ->lockForUpdate()
                ->first();

            $held = $holding?->quantity ?? 0;

            if ($holding === null || $held < $quantity) {
                throw new InsufficientSharesException($symbol, $held, $quantity);
            }

            $amount = bcmul((string) $quantity, $unitPrice, 4);
            $balance = bcadd($client->cash_balance, $amount, 4);
            $transaction = $this->record(
                $client,
                TransactionType::Sell,
                $amount,
                $balance,
                $symbol,
                $quantity,
                $unitPrice,
            );

            $remaining = $held - $quantity;

            if ($remaining === 0) {
                $holding->delete();
            } else {
                $holding->update(['quantity' => $remaining]);
            }

            return $transaction;
        });
    }

    private function record(
        Client $client,
        TransactionType $type,
        string $amount,
        string $balance,
        ?string $symbol = null,
        ?int $quantity = null,
        ?string $unitPrice = null,
    ): Transaction {
        $transaction = $client->transactions()->create([
            'type' => $type,
            'amount' => $amount,
            'symbol' => $symbol,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'cash_balance_after' => $balance,
            'created_at' => now(),
        ]);

        $client->forceFill(['cash_balance' => $balance])->save();

        return $transaction;
    }

    private function upsertHolding(Client $client, string $symbol, int $quantity): void
    {
        $holding = Holding::query()->firstOrNew([
            'client_id' => $client->id,
            'symbol' => $symbol,
        ]);

        $holding->quantity = (int) $holding->quantity + $quantity;
        $holding->save();
    }
}
