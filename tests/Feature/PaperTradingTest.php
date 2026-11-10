<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PaperTrade;
use App\Services\PaperTradingService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PaperTradingTest extends TestCase
{
    public function test_calculates_rrr_correctly_on_trade_creation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(PaperTradingService::class);

        $trade = $service->createTrade($user->id, [
            'symbol' => 'BTC',
            'side' => 'long',
            'entry_price' => 50000,
            'sl_price' => 48000,
            'tp_price' => 55000,
            'amount' => 1,
        ]);

        $this->assertEquals(2.5, $trade->rrr_ratio);
        $this->assertEquals('open', $trade->status);
    }

    public function test_calculates_virtual_pnl_on_close(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(PaperTradingService::class);

        $trade = $service->createTrade($user->id, [
            'symbol' => 'BTC',
            'side' => 'long',
            'entry_price' => 50000,
            'sl_price' => 48000,
            'tp_price' => 55000,
            'amount' => 1,
        ]);

        $closed = $service->closeTrade($trade, 54000);

        $this->assertEquals(4000, $closed->pnl);
        $this->assertEquals('closed', $closed->status);
    }

    public function test_calculates_short_position_pnl_correctly(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(PaperTradingService::class);

        $trade = $service->createTrade($user->id, [
            'symbol' => 'BTC',
            'side' => 'short',
            'entry_price' => 50000,
            'sl_price' => 52000,
            'tp_price' => 45000,
            'amount' => 1,
        ]);

        $closed = $service->closeTrade($trade, 48000);

        $this->assertEquals(2000, $closed->pnl);
        $this->assertEquals('closed', $closed->status);
    }

    public function test_auto_closes_trade_when_sl_tp_hit(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(PaperTradingService::class);

        $trade = $service->createTrade($user->id, [
            'symbol' => 'BTC',
            'side' => 'long',
            'entry_price' => 50000,
            'sl_price' => 48000,
            'tp_price' => 55000,
            'amount' => 1,
        ]);

        Cache::put('price:BTC', 47900, 60);
        $service->evaluateOpenTrades($user->id);

        $trade->refresh();
        $this->assertEquals('closed', $trade->status);
        // SL at 48000, close at 48000: (48000 - 50000) * 1 = -2000
        $this->assertEquals(-2000, $trade->pnl);
    }
}
