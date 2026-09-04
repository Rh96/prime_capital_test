<?php

namespace App\Console\Commands;

use App\Enums\TransactionType;
use App\Models\Client;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

#[Signature('ledger:verify {client? : The client ID to verify}')]
#[Description('Recompute cash and holdings from the transaction log and compare them to stored values')]
class VerifyLedgerCommand extends Command
{
    public function handle(): int
    {
        $clients = $this->clients();

        if ($clients->isEmpty()) {
            $this->error('No clients found.');

            return self::FAILURE;
        }

        $mismatches = 0;

        foreach ($clients as $client) {
            $mismatches += $this->reportMismatches($client);
        }

        if ($mismatches > 0) {
            $this->error("Found {$mismatches} mismatch(es). The ledger is the source of truth.");

            return self::FAILURE;
        }

        $this->info('Stored cash and holdings match the transaction log.');

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Client>
     */
    private function clients(): Collection
    {
        $id = $this->argument('client');

        if ($id === null) {
            return Client::query()->orderBy('id')->get();
        }

        return Client::query()->whereKey($id)->get();
    }

    private function reportMismatches(Client $client): int
    {
        [$cash, $holdings] = $this->replay($client);
        $count = 0;

        if (bccomp($client->cash_balance, $cash, 4) !== 0) {
            $this->error("{$client->name}: cash stored {$client->cash_balance}, ledger {$cash}.");
            $count++;
        }

        $stored = $client->holdings()->get()->keyBy('symbol');

        foreach ($holdings as $symbol => $quantity) {
            $held = (int) ($stored[$symbol]->quantity ?? 0);

            if ($held !== $quantity) {
                $this->error("{$client->name}: {$symbol} stored {$held}, ledger {$quantity}.");
                $count++;
            }
        }

        foreach ($stored as $symbol => $holding) {
            if (! array_key_exists($symbol, $holdings)) {
                $this->error("{$client->name}: {$symbol} stored {$holding->quantity}, ledger 0.");
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return array{0: string, 1: array<string, int>}
     */
    private function replay(Client $client): array
    {
        $cash = '0.0000';
        $holdings = [];

        foreach ($client->transactions()->orderBy('created_at')->orderBy('id')->get() as $transaction) {
            $cash = $transaction->type->movesCashIn()
                ? bcadd($cash, $transaction->amount, 4)
                : bcsub($cash, $transaction->amount, 4);

            if (! $transaction->type->isTrade()) {
                continue;
            }

            $delta = $transaction->type === TransactionType::Buy
                ? (int) $transaction->quantity
                : -(int) $transaction->quantity;

            $holdings[$transaction->symbol] = ($holdings[$transaction->symbol] ?? 0) + $delta;

            if ($holdings[$transaction->symbol] === 0) {
                unset($holdings[$transaction->symbol]);
            }
        }

        return [$cash, $holdings];
    }
}
