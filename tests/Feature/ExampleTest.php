<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

<<<<<<< HEAD
        $response->assertStatus(200);
=======
        $response->assertRedirect('/dashboard');
>>>>>>> cd7ddb7 (chore: initialize laravel project with breeze livewire stack)
    }
}
