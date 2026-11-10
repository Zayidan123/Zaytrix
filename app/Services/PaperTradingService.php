<?php
namespace App\Services;

use App\Models\PaperTrade;
use App\Models\Notification;
use Illuminate\Support\Facades\Cache;

class PaperTradingService
{
    public function createTrade(int $userId, array $data): PaperTrade
    {
        $trade = PaperTrade::create([
            'user_id' => $userId,
            'symbol' => $data['symbol'],
            'side' => $data['side'],
            'entry_price' => $data['entry_price'],
            'sl_price' => $data['sl_price'] ?? null,
            'tp_price' => $data['tp_price'] ?? null,
            'amount' => $data['amount'],
            'status' => 'open',
        ]);

        $rrr = $trade->calculateRrr();
        if ($rrr !== null) {
            $trade->update(['rrr_ratio' => $rrr]);
        }

        return $trade;
    }

    public function closeTrade(PaperTrade $trade, float $exitPrice): PaperTrade
    {
        $pnl = $trade->calculatePnl($exitPrice);
        $trade->update([
            'status' => 'closed',
            'pnl' => $pnl,
        ]);
        return $trade;
    }

    public function evaluateOpenTrades(int $userId): void
    {
        $openTrades = PaperTrade::where('user_id', $userId)
            ->where('status', 'open')
            ->get();

        foreach ($openTrades as $trade) {
            $currentPrice = $this->getCurrentPrice($trade->symbol);

            if ($trade->sl_price && $currentPrice <= $trade->sl_price) {
                $this->closeTrade($trade, $trade->sl_price);
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'paper_stop_loss',
                    'title' => "Stop Loss: {$trade->symbol}",
                    'body' => "Posisi {$trade->side} {$trade->symbol} di-close di SL {$trade->sl_price}",
                ]);
            } elseif ($trade->tp_price && $currentPrice >= $trade->tp_price) {
                $this->closeTrade($trade, $trade->tp_price);
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'paper_take_profit',
                    'title' => "Take Profit: {$trade->symbol}",
                    'body' => "Posisi {$trade->side} {$trade->symbol} di-close di TP {$trade->tp_price}",
                ]);
            }
        }
    }

    public function getSummary(int $userId): array
    {
        $trades = PaperTrade::where('user_id', $userId)->get();
        $open = $trades->where('status', 'open')->count();
        $closed = $trades->where('status', 'closed')->count();
        $totalPnl = $trades->where('status', 'closed')->sum('pnl');
        $winning = $trades->where('status', 'closed')->where('pnl', '>', 0)->count();
        $winRate = $closed > 0 ? ($winning / $closed) * 100 : 0;

        return [
            'total_trades' => $trades->count(),
            'open_trades' => $open,
            'closed_trades' => $closed,
            'total_pnl' => $totalPnl,
            'win_rate' => $winRate,
            'trades' => $trades->map(fn($t) => [
                'id' => $t->id,
                'symbol' => $t->symbol,
                'side' => $t->side,
                'entry_price' => $t->entry_price,
                'amount' => $t->amount,
                'status' => $t->status,
                'pnl' => $t->pnl,
                'rrr_ratio' => $t->rrr_ratio,
            ]),
        ];
    }

    private function getCurrentPrice(string $symbol): float
    {
        return Cache::get("price:{$symbol}", 0);
    }
}
