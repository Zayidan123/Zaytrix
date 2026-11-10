<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebauthnCredential extends Model
{
    protected $table = 'webauthn_credentials';
    protected $fillable = ['user_id', 'credential_id', 'name', 'public_key', 'counter', 'transports', 'last_used_at'];
    protected $casts = ['transports' => 'array', 'last_used_at' => 'datetime', 'counter' => 'integer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
