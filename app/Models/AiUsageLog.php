<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AiUsageLog extends Model
{
    protected $table = 'ai_usage_logs';
    protected $fillable = ['user_id', 'provider', 'model', 'prompt_tokens', 'completion_tokens', 'response_time_ms', 'cost_estimate', 'context_used'];
    protected $casts = ['context_used' => 'array'];
}
