<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <p class="mb-4">
                    <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:text-indigo-900" wire:navigate>{{ __('Clients') }}</a>
                </p>

                <h2 class="font-semibold text-xl">{{ $client->name }}</h2>
                <p class="mt-2">{{ __('Cash') }}: {{ $client->cash_balance }}</p>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h3 class="font-semibold text-lg mb-4">{{ __('Holdings') }}</h3>

                <table class="w-full text-left">
                    <thead>
                        <tr>
                            <th class="pb-2 pr-4">{{ __('Symbol') }}</th>
                            <th class="pb-2">{{ __('Quantity') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($holdings as $holding)
                            <tr wire:key="holding-{{ $holding->id }}">
                                <td class="py-2 pr-4">{{ $holding->symbol }}</td>
                                <td class="py-2">{{ $holding->quantity }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="py-2 text-gray-500">{{ __('No holdings.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h3 class="font-semibold text-lg mb-4">{{ __('Transactions') }}</h3>

                <table class="w-full text-left">
                    <thead>
                        <tr>
                            <th class="pb-2 pr-4">{{ __('Type') }}</th>
                            <th class="pb-2 pr-4">{{ __('Amount') }}</th>
                            <th class="pb-2 pr-4">{{ __('Symbol') }}</th>
                            <th class="pb-2 pr-4">{{ __('Qty') }}</th>
                            <th class="pb-2 pr-4">{{ __('Price') }}</th>
                            <th class="pb-2">{{ __('Cash after') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            <tr wire:key="transaction-{{ $transaction->id }}">
                                <td class="py-2 pr-4">{{ $transaction->type->value }}</td>
                                <td class="py-2 pr-4">{{ $transaction->amount }}</td>
                                <td class="py-2 pr-4">{{ $transaction->symbol ?? '—' }}</td>
                                <td class="py-2 pr-4">{{ $transaction->quantity ?? '—' }}</td>
                                <td class="py-2 pr-4">{{ $transaction->unit_price ?? '—' }}</td>
                                <td class="py-2">{{ $transaction->cash_balance_after }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-2 text-gray-500">{{ __('No transactions.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
