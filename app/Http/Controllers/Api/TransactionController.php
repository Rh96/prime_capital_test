<?php

namespace App\Http\Controllers\Api;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Client;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    public function index(Client $client): AnonymousResourceCollection
    {
        $transactions = $client->transactions()
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate();

        return TransactionResource::collection($transactions);
    }

    public function store(StoreTransactionRequest $request, Client $client, LedgerService $ledger): JsonResponse
    {
        $type = $request->enum('type', TransactionType::class);

        $transaction = match ($type) {
            TransactionType::Deposit => $ledger->deposit($client, (string) $request->input('amount')),
            TransactionType::Withdraw => $ledger->withdraw($client, (string) $request->input('amount')),
            TransactionType::Buy => $ledger->buy(
                $client,
                (string) $request->input('symbol'),
                (int) $request->input('quantity'),
                (string) $request->input('unit_price'),
            ),
            TransactionType::Sell => $ledger->sell(
                $client,
                (string) $request->input('symbol'),
                (int) $request->input('quantity'),
                (string) $request->input('unit_price'),
            ),
        };

        return (new TransactionResource($transaction))
            ->response()
            ->setStatusCode(201);
    }
}
