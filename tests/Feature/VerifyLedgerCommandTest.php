<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyLedgerCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_fails_when_cash_balance_is_tampered_with(): void
    {
        $this->seed();

        $ana = Client::query()->where('name', 'Ana')->firstOrFail();
        $ana->forceFill(['cash_balance' => '1.0000'])->save();

        $this->artisan('ledger:verify', ['client' => $ana->id])
            ->expectsOutputToContain('cash stored 1.0000, ledger 860.0000')
            ->assertFailed();
    }

    public function test_command_succeeds_when_stored_values_match_the_ledger(): void
    {
        $this->seed();

        $this->artisan('ledger:verify')
            ->assertSuccessful();
    }
}
