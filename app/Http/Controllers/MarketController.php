<?php
namespace App\Http\Controllers;
use App\Services\MarketDataService;
use App\Services\OnChainService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MarketController extends Controller
{
    public function __construct(
        protected MarketDataService $market,
        protected OnChainService $onchain,
    ) {}

    public function index(Request $request)
    {
        return Inertia::render('Market/Dashboard', [
            'coins' => $this->market->getTopCoins(500),
            'global' => $this->market->getGlobal(),
            'onchain' => $this->onchain->summary(),
            'whales' => Cache::get('whale:latest', ['whales' => [], 'last_price' => null, 'captured_at' => null]),
        ]);
    }
}