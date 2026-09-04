<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount' => [
                'required_if:type,deposit,withdraw',
                'numeric',
                'gt:0',
                'decimal:0,4',
            ],
            'symbol' => [
                'required_if:type,buy,sell',
                'prohibited_unless:type,buy,sell',
                'string',
                'max:16',
            ],
            'quantity' => [
                'required_if:type,buy,sell',
                'integer',
                'min:1',
            ],
            'unit_price' => [
                'required_if:type,buy,sell',
                'numeric',
                'gt:0',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Choose a movement type: deposit, withdraw, buy, or sell.',
            'type.enum' => 'Type must be deposit, withdraw, buy, or sell.',
            'amount.required_if' => 'An amount is required for deposits and withdrawals.',
            'amount.numeric' => 'Amount must be a number.',
            'amount.gt' => 'Amount must be greater than zero.',
            'amount.decimal' => 'Amount can have at most 4 decimal places.',
            'symbol.required_if' => 'A symbol is required when buying or selling.',
            'symbol.prohibited_unless' => 'Deposits and withdrawals cannot include a symbol.',
            'symbol.string' => 'Symbol must be text.',
            'symbol.max' => 'Symbol may not be longer than 16 characters.',
            'quantity.required_if' => 'A quantity is required when buying or selling.',
            'quantity.integer' => 'Quantity must be a whole number of shares.',
            'quantity.min' => 'Quantity must be at least 1 share.',
            'unit_price.required_if' => 'A unit price is required when buying or selling.',
            'unit_price.numeric' => 'Unit price must be a number.',
            'unit_price.gt' => 'Unit price must be greater than zero.',
        ];
    }
}
