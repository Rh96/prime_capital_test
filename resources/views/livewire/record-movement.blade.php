<div>
    <h3 class="font-semibold text-lg mb-4">{{ __('Record movement') }}</h3>

    <form wire:submit="save" class="space-y-4">
        <div>
            <x-input-label for="type" :value="__('Type')" />
            <select wire:model.live="type" id="type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                @foreach (\App\Enums\TransactionType::cases() as $transactionType)
                    <option value="{{ $transactionType->value }}">{{ $transactionType->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('type')" class="mt-2" />
        </div>

        @if (! $this->isTrade())
            <div>
                <x-input-label for="amount" :value="__('Amount')" />
                <x-text-input wire:model="amount" id="amount" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
            </div>
        @else
            <div>
                <x-input-label for="symbol" :value="__('Symbol')" />
                <x-text-input wire:model="symbol" id="symbol" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('symbol')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="quantity" :value="__('Quantity')" />
                <x-text-input wire:model.live="quantity" id="quantity" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="unit_price" :value="__('Unit price')" />
                <x-text-input wire:model.live="unit_price" id="unit_price" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('unit_price')" class="mt-2" />
            </div>

            @if ($this->totalPreview() !== null)
                <p class="text-sm text-gray-600">
                    {{ __('Total') }}: {{ $this->totalPreview() }}
                </p>
            @endif
        @endif

        <x-primary-button>{{ __('Record') }}</x-primary-button>
    </form>
</div>
