<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_increases_balance_and_writes_one_transaction_row(): void
    {
        $client = Client::factory()->create();

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '1000',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.type', 'deposit')
            ->assertJsonPath('data.amount', '1000.0000')
            ->assertJsonPath('data.cash_balance_after', '1000.0000');

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'cash_balance' => '1000.0000',
        ]);
        $this->assertDatabaseHas('transactions', [
            'client_id' => $client->id,
            'type' => 'deposit',
            'amount' => '1000.0000',
            'cash_balance_after' => '1000.0000',
            'symbol' => null,
        ]);
    }

    public function test_withdraw_decreases_balance(): void
    {
        $client = Client::factory()->create();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '1000',
        ])->assertCreated();

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'withdraw',
            'amount' => '250',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.type', 'withdraw')
            ->assertJsonPath('data.amount', '250.0000')
            ->assertJsonPath('data.cash_balance_after', '750.0000');

        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'cash_balance' => '750.0000',
        ]);
        $this->assertDatabaseHas('transactions', [
            'client_id' => $client->id,
            'type' => 'withdraw',
            'amount' => '250.0000',
            'cash_balance_after' => '750.0000',
        ]);
    }

    public function test_withdrawing_more_than_the_balance_is_rejected_and_the_balance_is_unchanged_afterwards(): void
    {
        $client = Client::factory()->create();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '500',
        ])->assertCreated();

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'withdraw',
            'amount' => '501',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('code', 'insufficient_funds')
            ->assertJsonPath('message', 'Insufficient funds: available 500.0000, requested 501.');

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'cash_balance' => '500.0000',
        ]);
    }

    public function test_cash_balance_after_on_each_row_matches_the_clients_balance_at_that_point(): void
    {
        $client = Client::factory()->create();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '1000',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'withdraw',
            'amount' => '400',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '50.25',
        ])->assertCreated();

        $this->assertDatabaseHas('transactions', [
            'client_id' => $client->id,
            'type' => 'deposit',
            'amount' => '1000.0000',
            'cash_balance_after' => '1000.0000',
        ]);
        $this->assertDatabaseHas('transactions', [
            'client_id' => $client->id,
            'type' => 'withdraw',
            'amount' => '400.0000',
            'cash_balance_after' => '600.0000',
        ]);
        $this->assertDatabaseHas('transactions', [
            'client_id' => $client->id,
            'type' => 'deposit',
            'amount' => '50.2500',
            'cash_balance_after' => '650.2500',
        ]);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'cash_balance' => '650.2500',
        ]);
    }

    public function test_amount_of_zero_or_negative_is_rejected(): void
    {
        $client = Client::factory()->create();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '0',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'withdraw',
            'amount' => '-10',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'cash_balance' => '0.0000',
        ]);
    }
}
