<?php

namespace Tests\Feature;

use App\Livewire\ClientList;
use App\Livewire\ClientPortfolio;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_away_from_the_client_list_and_portfolio(): void
    {
        $client = Client::factory()->create();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('clients.show', $client))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_the_client_list(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'staff@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ana')
            ->assertSee('860.0000')
            ->assertSee('Elena');

        Livewire::actingAs($user)
            ->test(ClientList::class)
            ->assertSee('Ana')
            ->assertSee('Boris');
    }

    public function test_authenticated_users_can_view_a_client_portfolio(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'staff@example.com')->firstOrFail();
        $ana = Client::query()->where('name', 'Ana')->firstOrFail();

        $this->actingAs($user)
            ->get(route('clients.show', $ana))
            ->assertOk()
            ->assertSee('Ana')
            ->assertSee('860.0000')
            ->assertSee('AAPL')
            ->assertSee('deposit')
            ->assertSee('buy')
            ->assertSee('sell');

        Livewire::actingAs($user)
            ->test(ClientPortfolio::class, ['client' => $ana])
            ->assertSee('Holdings')
            ->assertSee('Transactions')
            ->assertSee('2');
    }
}
