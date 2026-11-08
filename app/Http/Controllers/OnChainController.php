<?php
namespace App\Http\Controllers;

use App\Services\OnChainService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OnChainController extends Controller
{
    public function __construct(protected OnChainService $onchain) {}

    public function index(Request $request)
    {
        return Inertia::render('OnChain/Dashboard', [
            'onchain' => $this->onchain->summary(),
            // Baca dari cache (diisi oleh PollWhales job) agar halaman tidak memblok
            // pada k WebSocket Binance setiap request.
            'whales' => Cache::get('whale:latest', ['whales' => [], 'last_price' => null, 'captured_at' => null]),
        ]);
    }
}