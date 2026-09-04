<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_buy_without_a_symbol_is_rejected(): void
    {
        $client = Client::factory()->create();

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'buy',
            'quantity' => 1,
            'unit_price' => '10',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['symbol']);

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('holdings', 0);
    }

    public function test_deposit_with_a_symbol_is_rejected(): void
    {
        $client = Client::factory()->create();

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '100',
            'symbol' => 'AAPL',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['symbol']);

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'cash_balance' => '0.0000',
        ]);
    }

    public function test_fractional_quantity_is_rejected(): void
    {
        $client = Client::factory()->create();

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'buy',
            'symbol' => 'AAPL',
            'quantity' => 1.5,
            'unit_price' => '10',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('holdings', 0);
    }

    public function test_unknown_transaction_type_is_rejected(): void
    {
        $client = Client::factory()->create();

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'transfer',
            'amount' => '100',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_cash_and_holdings_endpoints_return_the_expected_shape(): void
    {
        $client = Client::factory()->create();

        $this->getJson("/api/clients/{$client->id}/cash")
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['client_id', 'cash_balance'],
            ])
            ->assertJsonPath('data.client_id', $client->id)
            ->assertJsonPath('data.cash_balance', '0.0000');

        $this->getJson("/api/clients/{$client->id}/holdings")
            ->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonPath('data', []);

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '1000',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'buy',
            'symbol' => 'AAPL',
            'quantity' => 2,
            'unit_price' => '100',
        ])->assertCreated();

        $this->getJson("/api/clients/{$client->id}/cash")
            ->assertOk()
            ->assertJsonPath('data.client_id', $client->id)
            ->assertJsonPath('data.cash_balance', '800.0000');

        $this->getJson("/api/clients/{$client->id}/holdings")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['id', 'client_id', 'symbol', 'quantity'],
                ],
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.client_id', $client->id)
            ->assertJsonPath('data.0.symbol', 'AAPL')
            ->assertJsonPath('data.0.quantity', 2);
    }
}
