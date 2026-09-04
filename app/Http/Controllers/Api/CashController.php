<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CashResource;
use App\Models\Client;

class CashController extends Controller
{
    public function show(Client $client): CashResource
    {
        return new CashResource($client);
    }
}
