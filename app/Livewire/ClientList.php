<?php

namespace App\Livewire;

use App\Models\Client;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ClientList extends Component
{
    public function render(): View
    {
        return view('livewire.client-list', [
            'clients' => Client::query()->orderBy('name')->get(),
        ]);
    }
}
