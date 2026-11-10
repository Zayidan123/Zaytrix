<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AlertConfig;
use App\Models\Notification;
use App\Services\AlertService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AlertsTest extends TestCase
{
    public function test_creates_alert_and_fires_notification_when_target_reached(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(AlertService::class);

        $alert = AlertConfig::create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'condition' => 'above',
            'target_price' => 60000,
            'active' => true,
        ]);

        $this->assertEquals($user->id, $alert->user_id);
        $this->assertTrue($alert->active);

        Cache::put('price:BTC', 61000, 60);
        $service->checkAlerts($user->id);

        $notification = Notification::where('user_id', $user->id)->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('BTC', $notification->title);
        $this->assertFalse($notification->read);
    }

    public function test_does_not_fire_when_price_not_reached(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(AlertService::class);

        AlertConfig::create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'condition' => 'above',
            'target_price' => 70000,
            'active' => true,
        ]);

        Cache::put('price:BTC', 61000, 60);
        $service->checkAlerts($user->id);

        $this->assertEquals(0, Notification::where('user_id', $user->id)->count());
    }

    public function test_marks_notification_as_read(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(AlertService::class);

        AlertConfig::create([
            'user_id' => $user->id,
            'symbol' => 'ETH',
            'condition' => 'below',
            'target_price' => 2500,
            'active' => true,
        ]);

        Cache::put('price:ETH', 2400, 60);
        $service->checkAlerts($user->id);

        $notification = Notification::where('user_id', $user->id)->first();
        $service->markRead($notification->id);

        $notification->refresh();
        $this->assertTrue($notification->read);
    }
}
