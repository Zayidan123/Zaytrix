<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PaperTrade extends Model
{
    protected $table = 'paper_trades';
    protected $fillable = ['user_id', 'symbol', 'side', 'entry_price', 'stop_loss', 'take_profit', 'quantity', 'status', 'pnl', 'rrr_ratio', 'signal_id', 'notes'];
    protected $casts = ['entry_price' => 'decimal:8', 'stop_loss' => 'decimal:8', 'take_profit' => 'decimal:8', 'quantity' => 'decimal:8', 'pnl' => 'decimal:8', 'rrr_ratio' => 'decimal:4'];
}
