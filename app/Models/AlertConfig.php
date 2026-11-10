<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AlertConfig extends Model
{
    protected $fillable = ['user_id', 'symbol', 'condition', 'target_price', 'active'];
    protected $casts = ['target_price' => 'decimal:8', 'active' => 'boolean'];
}
