<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PaperTrade extends Model
{
    protected $fillable = ['user_id', 'symbol', 'side', 'entry_price', 'sl_price', 'tp_price', 'amount', 'status', 'pnl', 'rrr_ratio', 'signal_id', 'notes'];
    protected $casts = [
        'entry_price' => 'decimal:8',
        'sl_price' => 'decimal:8',
        'tp_price' => 'decimal:8',
        'amount' => 'decimal:8',
        'pnl' => 'decimal:8',
        'rrr_ratio' => 'decimal:4',
        'status' => 'string',
    ];

    public function calculateRrr(): ?float
    {
        if (!$this->sl_price || !$this->tp_price) return null;
        $risk = abs($this->entry_price - $this->sl_price);
        $reward = abs($this->tp_price - $this->entry_price);
        return $risk > 0 ? $reward / $risk : null;
    }

    public function calculatePnl(float $currentPrice): float
    {
        $riskPerUnit = $this->side === 'long'
            ? $currentPrice - $this->entry_price
            : $this->entry_price - $currentPrice;
        return $riskPerUnit * $this->amount;
    }
}
