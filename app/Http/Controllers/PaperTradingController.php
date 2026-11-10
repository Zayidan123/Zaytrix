<?php
namespace App\Http\Controllers;

use App\Services\PaperTradingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaperTradingController extends Controller
{
    public function __construct(
        private PaperTradingService $paperTradingService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => 'required|string|max:10',
            'side' => 'required|in:long,short',
            'entry_price' => 'required|numeric|min:0',
            'sl_price' => 'nullable|numeric|min:0',
            'tp_price' => 'nullable|numeric|min:0',
            'amount' => 'required|numeric|min:0.00000001',
        ]);

        $trade = $this->paperTradingService->createTrade($request->user()->id, $validated);
        return response()->json(['success' => true, 'trade' => $trade], 201);
    }

    public function summary(Request $request): JsonResponse
    {
        $summary = $this->paperTradingService->getSummary($request->user()->id);
        return response()->json($summary);
    }

    public function index(Request $request): JsonResponse
    {
        $trades = \App\Models\PaperTrade::where('user_id', $request->user()->id)
            ->latest()
            ->limit(100)
            ->get();
        return response()->json($trades);
    }
}
