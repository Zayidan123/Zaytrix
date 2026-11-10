<?php
namespace App\Http\Controllers;

use App\Services\PortfolioService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PortfolioController extends Controller
{
    public function __construct(
        private PortfolioService $portfolioService
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $summary = $this->portfolioService->getSummary($user->id);
        return response()->json($summary);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => 'required|string|max:10',
            'type' => 'required|in:buy,sell',
            'amount' => 'required|numeric|min:0.00000001',
            'price' => 'required|numeric|min:0',
            'fee' => 'nullable|numeric|min:0',
        ]);

        $tx = $this->portfolioService->addTransaction($request->user()->id, $validated);
        return response()->json(['success' => true, 'transaction' => $tx], 201);
    }

    public function history(Request $request): JsonResponse
    {
        $txs = \App\Models\Transaction::where('user_id', $request->user()->id)
            ->latest('executed_at')
            ->limit(100)
            ->get();
        return response()->json($txs);
    }
}
