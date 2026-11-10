<?php
namespace App\Services;

use App\Models\Portfolio;
use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PortfolioService
{
    public function addTransaction(int $userId, array $data): Transaction
    {
        return DB::transaction(function () use ($userId, $data) {
            $tx = Transaction::create([
                'user_id' => $userId,
                'symbol' => $data['symbol'],
                'type' => $data['type'],
                'amount' => $data['amount'],
                'price' => $data['price'],
                'fee' => $data['fee'] ?? 0,
                'executed_at' => now(),
            ]);

            $portfolio = Portfolio::where('user_id', $userId)
                ->where('symbol', $data['symbol'])
                ->first();

            if (!$portfolio) {
                $portfolio = Portfolio::create([
                    'user_id' => $userId,
                    'symbol' => $data['symbol'],
                    'amount' => 0,
                    'avg_price' => 0,
                    'value' => 0,
                ]);
            }

            $oldAmount = (float) $portfolio->amount;
            $oldAvg = (float) $portfolio->avg_price;
            $newAmount = $data['amount'];
            $newPrice = $data['price'];

            if ($data['type'] === 'buy') {
                $totalCost = ($oldAmount * $oldAvg) + ($newAmount * $newPrice) + ($data['fee'] ?? 0);
                $totalAmount = $oldAmount + $newAmount;
                $portfolio->update([
                    'amount' => $totalAmount,
                    'avg_price' => $totalAmount > 0 ? $totalCost / $totalAmount : 0,
                ]);
            } else {
                $sellAmount = min($newAmount, $oldAmount);
                $costBasis = $sellAmount * $oldAvg;
                $revenue = $sellAmount * $newPrice;
                $pnl = $revenue - $costBasis - ($data['fee'] ?? 0);
                $tx->update(['pnl' => $pnl]);
                $portfolio->update([
                    'amount' => max(0, $oldAmount - $sellAmount),
                ]);
            }

            $this->updateValue($portfolio);
            $this->clearCache($userId);
            return $tx;
        });
    }

    public function updateValue(Portfolio $portfolio): void
    {
        $price = $this->getCurrentPrice($portfolio->symbol);
        $portfolio->update(['value' => $portfolio->amount * $price]);
    }

    public function getCurrentPrice(string $symbol): float
    {
        $cacheKey = "price:{$symbol}";
        return Cache::get($cacheKey, 0);
    }

    public function clearCache(int $userId): void
    {
        Cache::forget("portfolio:{$userId}");
        Cache::forget("portfolio_summary:{$userId}");
    }

    public function getSummary(int $userId): array
    {
        return Cache::remember("portfolio_summary:{$userId}", 60, function () use ($userId) {
            $portfolios = Portfolio::where('user_id', $userId)->get();
            $totalValue = $portfolios->sum('value');
            $totalCost = $portfolios->sum(function ($p) {
                return $p->amount * $p->avg_price;
            });
            $totalPnl = $totalValue - $totalCost;

            return [
                'total_value' => $totalValue,
                'total_cost' => $totalCost,
                'total_pnl' => $totalPnl,
                'pnl_percent' => $totalCost > 0 ? ($totalPnl / $totalCost) * 100 : 0,
                'positions' => $portfolios->map(function ($p) {
                    $currentPrice = $this->getCurrentPrice($p->symbol);
                    return [
                        'symbol' => $p->symbol,
                        'amount' => $p->amount,
                        'avg_price' => $p->avg_price,
                        'current_price' => $currentPrice,
                        'value' => $p->value,
                        'pnl' => $p->value - ($p->amount * $p->avg_price),
                    ];
                })->values(),
            ];
        });
    }
}
