<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'type' => TransactionType::Deposit,
            'amount' => '100.0000',
            'symbol' => null,
            'quantity' => null,
            'unit_price' => null,
            'cash_balance_after' => '100.0000',
        ];
    }
}
