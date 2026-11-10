<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = ['user_id', 'symbol', 'type', 'amount', 'price', 'fee', 'executed_at', 'pnl'];
    protected $casts = ['amount' => 'decimal:8', 'price' => 'decimal:8', 'fee' => 'decimal:8', 'pnl' => 'decimal:8', 'executed_at' => 'datetime'];
}
