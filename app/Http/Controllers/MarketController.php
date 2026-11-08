<?php
namespace App\Http\Controllers;
use App\Services\MarketDataService;
use Inertia\Inertia;

class MarketController extends Controller
{
    public function __construct(protected MarketDataService $svc) {}
    public function index()
    {
        return Inertia::render('Market/Dashboard', [
            'coins' => $this->svc->getTopCoins(500),
            'global' => $this->svc->getGlobal(),
        ]);
    }
}
