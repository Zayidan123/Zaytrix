<?php
namespace App\Jobs;
use App\Services\MarketDataService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PollMarketData implements ShouldQueue
{
    use Queueable;
    public function handle(MarketDataService $svc): void
    {
        $svc->getTopCoins(250);
        $svc->getGlobal();
    }
}
