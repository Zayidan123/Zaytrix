<?php
namespace App\Jobs;
use App\Services\WhaleRadarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PollWhales implements ShouldQueue
{
    use Queueable;
    public function __construct(public int $windowMs = 8000, public float $whaleUsd = 50000) {}
    public function handle(WhaleRadarService $svc): void
    {
        $svc->fetchWhales('BTCUSDT', $this->windowMs, $this->whaleUsd);
    }
}
