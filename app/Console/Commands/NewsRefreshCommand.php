<?php
namespace App\Console\Commands;

use App\Services\NewsService;
use Illuminate\Console\Command;

class NewsRefreshCommand extends Command
{
    protected $signature = 'news:refresh';
    protected $description = 'Fetch and store latest news from RSS feeds';

    public function handle(NewsService $newsService): int
    {
        $this->info('Fetching news from RSS feeds...');
        $count = $newsService->refresh();
        $this->info("News refreshed: {$count} items stored.");
        return self::SUCCESS;
    }
}
