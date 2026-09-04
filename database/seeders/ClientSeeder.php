<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Services\LedgerService;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(LedgerService $ledger): void
    {
        $ana = Client::query()->create(['name' => 'Ana']);
        $ledger->deposit($ana, '1000');
        $ledger->buy($ana, 'AAPL', 5, '100');
        $ledger->sell($ana, 'AAPL', 3, '120');

        $boris = Client::query()->create(['name' => 'Boris']);
        $ledger->deposit($boris, '5000');
        $ledger->buy($boris, 'AAPL', 10, '150');
        $ledger->buy($boris, 'MSFT', 8, '200');
        $ledger->buy($boris, 'GOOG', 4, '120');

        $elena = Client::query()->create(['name' => 'Elena']);
        $ledger->deposit($elena, '2500');

        $ivana = Client::query()->create(['name' => 'Ivana']);
        $ledger->deposit($ivana, '2000');
        $ledger->buy($ivana, 'TSLA', 5, '200');
        $ledger->sell($ivana, 'TSLA', 5, '220');
    }
}
