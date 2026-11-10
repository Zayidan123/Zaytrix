<?php
namespace App\Services;

use App\Models\AlertConfig;
use App\Models\Notification;
use Illuminate\Support\Facades\Cache;

class AlertService
{
    public function checkAlerts(int $userId): int
    {
        $alerts = AlertConfig::where('user_id', $userId)
            ->where('active', true)
            ->get();

        $triggered = 0;
        foreach ($alerts as $alert) {
            $currentPrice = Cache::get("price:{$alert->symbol}", 0);
            if ($currentPrice <= 0) continue;

            $hit = match ($alert->condition) {
                'above' => $currentPrice >= $alert->target_price,
                'below' => $currentPrice <= $alert->target_price,
                default => false,
            };

            if ($hit) {
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'price_alert',
                    'title' => "Price Alert: {$alert->symbol}",
                    'body' => "{$alert->symbol} saat ini \${$currentPrice} (target {$alert->condition} \${$alert->target_price})",
                ]);
                $alert->update(['active' => false]);
                $triggered++;
            }
        }
        return $triggered;
    }

    public function checkAllUsers(): void
    {
        $userIds = AlertConfig::where('active', true)->distinct('user_id')->pluck('user_id');
        foreach ($userIds as $userId) {
            $this->checkAlerts($userId);
        }
    }

    public function markRead(int $notificationId): bool
    {
        $notification = Notification::find($notificationId);
        if ($notification) {
            $notification->update(['read' => true]);
            return true;
        }
        return false;
    }
}
