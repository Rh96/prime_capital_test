<?php

use App\Livewire\ClientList;
use App\Livewire\ClientPortfolio;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', ClientList::class)->name('dashboard');
    Route::get('clients/{client}', ClientPortfolio::class)->name('clients.show');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
