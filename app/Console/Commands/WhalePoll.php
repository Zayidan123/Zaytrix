<?php
namespace App\Console\Commands;
use App\Jobs\PollWhales;
use Illuminate\Console\Command;

class WhalePoll extends Command
{
    protected $signature = 'whale:poll {--window=8000} {--threshold=50000}';
    protected $description = 'Poll Binance trade stream for whale trades (bounded window)';
    public function handle(): int
    {
        PollWhales::dispatch((int)$this->option('window'), (float)$this->option('threshold'));
        $this->info('PollWhales dispatched');
        return self::SUCCESS;
    }
}
