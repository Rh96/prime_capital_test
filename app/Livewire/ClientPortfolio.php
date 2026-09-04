<?php

namespace App\Livewire;

use App\Models\Client;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ClientPortfolio extends Component
{
    use WithPagination;

    public Client $client;

    #[On('movement-recorded')]
    public function refreshAfterMovement(): void
    {
        $this->client->refresh();
    }

    public function render(): View
    {
        return view('livewire.client-portfolio', [
            'holdings' => $this->client->holdings()->orderBy('symbol')->get(),
            'transactions' => $this->client->transactions()
                ->orderBy('created_at')
                ->orderBy('id')
                ->paginate(15),
        ]);
    }
}
