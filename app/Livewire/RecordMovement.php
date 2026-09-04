<?php

namespace App\Livewire;

use App\Enums\TransactionType;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InsufficientSharesException;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Client;
use App\Services\LedgerService;
use Illuminate\View\View;
use Livewire\Component;

class RecordMovement extends Component
{
    public Client $client;

    public string $type = 'deposit';

    public string $amount = '';

    public string $symbol = '';

    public string $quantity = '';

    public string $unit_price = '';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return (new StoreTransactionRequest)->rules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return (new StoreTransactionRequest)->messages();
    }

    public function updatedType(): void
    {
        $this->reset('amount', 'symbol', 'quantity', 'unit_price');
        $this->resetErrorBag();
    }

    public function save(LedgerService $ledger): void
    {
        $this->validate();

        try {
            match (TransactionType::from($this->type)) {
                TransactionType::Deposit => $ledger->deposit($this->client, $this->amount),
                TransactionType::Withdraw => $ledger->withdraw($this->client, $this->amount),
                TransactionType::Buy => $ledger->buy(
                    $this->client,
                    $this->symbol,
                    (int) $this->quantity,
                    $this->unit_price,
                ),
                TransactionType::Sell => $ledger->sell(
                    $this->client,
                    $this->symbol,
                    (int) $this->quantity,
                    $this->unit_price,
                ),
            };
        } catch (InsufficientFundsException $e) {
            $this->addError('amount', $e->getMessage());

            return;
        } catch (InsufficientSharesException $e) {
            $this->addError('quantity', $e->getMessage());

            return;
        }

        $this->reset('amount', 'symbol', 'quantity', 'unit_price');
        $this->dispatch('movement-recorded');
    }

    public function isTrade(): bool
    {
        return TransactionType::tryFrom($this->type)?->isTrade() ?? false;
    }

    public function totalPreview(): ?string
    {
        if (! $this->isTrade()) {
            return null;
        }

        if ($this->quantity === '' || $this->unit_price === '') {
            return null;
        }

        if (! is_numeric($this->quantity) || ! is_numeric($this->unit_price)) {
            return null;
        }

        return bcmul($this->quantity, $this->unit_price, 4);
    }

    public function render(): View
    {
        return view('livewire.record-movement');
    }
}
