<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HoldingResource;
use App\Models\Client;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HoldingController extends Controller
{
    public function index(Client $client): AnonymousResourceCollection
    {
        return HoldingResource::collection(
            $client->holdings()->orderBy('symbol')->get()
        );
    }
}
