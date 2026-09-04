<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_buy_decrements_cash_and_creates_a_holding(): void
    {
        $client = Client::factory()->create();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '1000',
        ])->assertCreated();

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'buy',
            'symbol' => 'AAPL',
            'quantity' => 5,
            'unit_price' => '100',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.type', 'buy')
            ->assertJsonPath('data.amount', '500.0000')
            ->assertJsonPath('data.symbol', 'AAPL')
            ->assertJsonPath('data.quantity', 5)
            ->assertJsonPath('data.cash_balance_after', '500.0000');

        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseCount('holdings', 1);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'cash_balance' => '500.0000',
        ]);
        $this->assertDatabaseHas('holdings', [
            'client_id' => $client->id,
            'symbol' => 'AAPL',
            'quantity' => 5,
        ]);
    }

    public function test_buying_the_same_symbol_twice_accumulates_into_one_holding_row(): void
    {
        $client = Client::factory()->create();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '1000',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'buy',
            'symbol' => 'aapl',
            'quantity' => 2,
            'unit_price' => '100',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'buy',
            'symbol' => 'AAPL',
            'quantity' => 3,
            'unit_price' => '100',
        ])->assertCreated();

        $this->assertDatabaseCount('holdings', 1);
        $this->assertDatabaseHas('holdings', [
            'client_id' => $client->id,
            'symbol' => 'AAPL',
            'quantity' => 5,
        ]);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'cash_balance' => '500.0000',
        ]);
    }

    public function test_selling_more_shares_than_held_is_rejected_and_cash_and_holdings_are_unchanged(): void
    {
        $client = Client::factory()->create();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '1000',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'buy',
            'symbol' => 'AAPL',
            'quantity' => 5,
            'unit_price' => '100',
        ])->assertCreated();

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'sell',
            'symbol' => 'AAPL',
            'quantity' => 8,
            'unit_price' => '100',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('code', 'insufficient_shares')
            ->assertJsonPath('message', 'Insufficient shares of AAPL: held 5, requested 8.');

        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseCount('holdings', 1);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'cash_balance' => '500.0000',
        ]);
        $this->assertDatabaseHas('holdings', [
            'client_id' => $client->id,
            'symbol' => 'AAPL',
            'quantity' => 5,
        ]);
    }

    public function test_buying_beyond_available_cash_is_rejected(): void
    {
        $client = Client::factory()->create();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '500',
        ])->assertCreated();

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'buy',
            'symbol' => 'AAPL',
            'quantity' => 7,
            'unit_price' => '100',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('code', 'insufficient_funds')
            ->assertJsonPath('message', 'Insufficient funds: available 500.0000, requested 700.0000.');

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('holdings', 0);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'cash_balance' => '500.0000',
        ]);
    }

    public function test_selling_at_a_different_price_than_the_buy_price_credits_quantity_times_unit_price(): void
    {
        $client = Client::factory()->create();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '1000',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'buy',
            'symbol' => 'AAPL',
            'quantity' => 5,
            'unit_price' => '100',
        ])->assertCreated();

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'sell',
            'symbol' => 'AAPL',
            'quantity' => 3,
            'unit_price' => '120',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.type', 'sell')
            ->assertJsonPath('data.amount', '360.0000')
            ->assertJsonPath('data.cash_balance_after', '860.0000');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'cash_balance' => '860.0000',
        ]);
        $this->assertDatabaseHas('transactions', [
            'client_id' => $client->id,
            'type' => 'sell',
            'amount' => '360.0000',
            'quantity' => 3,
            'unit_price' => '120.0000',
            'cash_balance_after' => '860.0000',
        ]);
        $this->assertDatabaseHas('holdings', [
            'client_id' => $client->id,
            'symbol' => 'AAPL',
            'quantity' => 2,
        ]);
    }

    public function test_selling_the_entire_position_removes_the_holding_row(): void
    {
        $client = Client::factory()->create();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '1000',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'buy',
            'symbol' => 'AAPL',
            'quantity' => 5,
            'unit_price' => '100',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'sell',
            'symbol' => 'AAPL',
            'quantity' => 5,
            'unit_price' => '100',
        ])->assertCreated();

        $this->assertDatabaseCount('holdings', 0);
        $this->assertDatabaseMissing('holdings', [
            'client_id' => $client->id,
            'symbol' => 'AAPL',
        ]);
        $this->assertDatabaseCount('transactions', 3);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'cash_balance' => '1000.0000',
        ]);
    }

    public function test_the_spec_worked_example_end_to_end(): void
    {
        $client = Client::factory()->create(['name' => 'Ana']);

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '1000',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'buy',
            'symbol' => 'AAPL',
            'quantity' => 5,
            'unit_price' => '100',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'sell',
            'symbol' => 'AAPL',
            'quantity' => 3,
            'unit_price' => '120',
        ])->assertCreated();

        $this->getJson("/api/clients/{$client->id}/cash")
            ->assertOk()
            ->assertJsonPath('data.cash_balance', '860.0000');

        $this->getJson("/api/clients/{$client->id}/holdings")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.symbol', 'AAPL')
            ->assertJsonPath('data.0.quantity', 2);

        $this->assertDatabaseCount('transactions', 3);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'cash_balance' => '860.0000',
        ]);
        $this->assertDatabaseHas('holdings', [
            'client_id' => $client->id,
            'symbol' => 'AAPL',
            'quantity' => 2,
        ]);
    }
}
