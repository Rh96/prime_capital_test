<?php

use App\Http\Controllers\Api\CashController;
use App\Http\Controllers\Api\HoldingController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('/clients/{client}/transactions', [TransactionController::class, 'store']);
Route::get('/clients/{client}/transactions', [TransactionController::class, 'index']);
Route::get('/clients/{client}/cash', [CashController::class, 'show']);
Route::get('/clients/{client}/holdings', [HoldingController::class, 'index']);
