<?php
namespace App\Http\Controllers;

use App\Services\MarketDataService;
use App\Services\OnChainService;
use App\Services\PortfolioService;
use App\Services\PaperTradingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MarketController extends Controller
{
    public function __construct(
        protected MarketDataService $market,
        protected OnChainService $onchain,
        protected PortfolioService $portfolio,
        protected PaperTradingService $paperTrading,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $portfolioSummary = $user ? $this->portfolio->getSummary($user->id) : null;
        $paperSummary = $user ? $this->paperTrading->getSummary($user->id) : null;

        return Inertia::render('Market/Dashboard', [
            'coins' => $this->market->getTopCoins(500),
            'global' => $this->market->getGlobal(),
            'onchain' => $this->onchain->summary(),
            'whales' => Cache::get('whale:latest', ['whales' => [], 'last_price' => null, 'captured_at' => null]),
            'portfolio' => $portfolioSummary,
            'paperTrading' => $paperSummary,
        ]);
    }
}
