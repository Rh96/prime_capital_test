<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'type' => $this->type->value,
            'amount' => $this->amount,
            'symbol' => $this->symbol,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'cash_balance_after' => $this->cash_balance_after,
            'created_at' => $this->created_at,
        ];
    }
}
