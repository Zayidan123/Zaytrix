<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Portfolio extends Model
{
    protected $fillable = ['user_id', 'symbol', 'amount', 'avg_price', 'value'];
    protected $casts = ['amount' => 'decimal:8', 'avg_price' => 'decimal:8', 'value' => 'decimal:8'];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'symbol', 'symbol')
            ->where('user_id', $this->user_id);
    }
}
