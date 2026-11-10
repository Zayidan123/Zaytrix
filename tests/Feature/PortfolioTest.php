<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Services\PortfolioService;
use Tests\TestCase;

class PortfolioTest extends TestCase
{
    public function test_calculates_pnl_correctly_on_buy_then_sell(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(PortfolioService::class);

        $service->addTransaction($user->id, [
            'symbol' => 'BTC',
            'type' => 'buy',
            'amount' => 1,
            'price' => 50000,
            'fee' => 50,
        ]);

        $service->addTransaction($user->id, [
            'symbol' => 'BTC',
            'type' => 'sell',
            'amount' => 0.5,
            'price' => 60000,
            'fee' => 30,
        ]);

        $portfolio = Portfolio::where('user_id', $user->id)->where('symbol', 'BTC')->first();
        $this->assertEquals(0.5, $portfolio->amount);
        $this->assertEquals(50050, $portfolio->avg_price);
        $this->assertEquals(0, $portfolio->value);

        $transactions = Transaction::where('user_id', $user->id)->get();
        $buyTx = $transactions->where('type', 'buy')->first();
        $sellTx = $transactions->where('type', 'sell')->first();

        // Buy pnl is 0 (default), not null
        $this->assertEquals(0, $buyTx->pnl);
        $this->assertEquals(4945, $sellTx->pnl); // (60000-50050)*0.5 - 30 = 4975 - 30 = 4945
    }

    public function test_calculates_total_portfolio_summary_correctly(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(PortfolioService::class);

        $service->addTransaction($user->id, ['symbol' => 'BTC', 'type' => 'buy', 'amount' => 1, 'price' => 50000, 'fee' => 50]);
        $service->addTransaction($user->id, ['symbol' => 'ETH', 'type' => 'buy', 'amount' => 10, 'price' => 3000, 'fee' => 30]);

        $summary = $service->getSummary($user->id);

        $this->assertCount(2, $summary['positions']);
    }
}
