<?php

namespace Tests\Feature;

use App\Livewire\RecordMovement;
use App\Models\Client;
use App\Models\User;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecordMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_over_withdrawal_shows_a_form_error_and_creates_no_row(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        app(LedgerService::class)->deposit($client, '100');

        Livewire::actingAs($user)
            ->test(RecordMovement::class, ['client' => $client])
            ->set('type', 'withdraw')
            ->set('amount', '101')
            ->call('save')
            ->assertHasErrors(['amount'])
            ->assertSee('Insufficient funds: available 100.0000, requested 101.');

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'cash_balance' => '100.0000',
        ]);
    }
}
