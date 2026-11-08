<?php
namespace App\Console\Commands;
use App\Jobs\PollMarketData;
use Illuminate\Console\Command;

class PollMarket extends Command
{
    protected $signature = 'market:poll';
    protected $description = 'Poll CoinGecko market data into cache';
    public function handle(): int
    {
        PollMarketData::dispatch();
        $this->info('PollMarketData dispatched');
        return self::SUCCESS;
    }
}
