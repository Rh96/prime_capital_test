<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h2 class="font-semibold text-xl mb-4">{{ __('Clients') }}</h2>

                <table class="w-full text-left">
                    <thead>
                        <tr>
                            <th class="pb-2 pr-4">{{ __('Name') }}</th>
                            <th class="pb-2">{{ __('Cash') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $client)
                            <tr wire:key="client-{{ $client->id }}">
                                <td class="py-2 pr-4">
                                    <a href="{{ route('clients.show', $client) }}" class="text-indigo-600 hover:text-indigo-900" wire:navigate>
                                        {{ $client->name }}
                                    </a>
                                </td>
                                <td class="py-2">{{ $client->cash_balance }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="py-2 text-gray-500">{{ __('No clients yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
