<?php
namespace App\Services;

use App\Models\News;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NewsService
{
    private array $sources = [
        'CoinDesk' => 'https://www.coindesk.com/arc/outboundfeeds/rss/',
        'Cointelegraph' => 'https://cointelegraph.com/rss',
        'Bitcoin Magazine' => 'https://bitcoinmagazine.com/.rss/full/',
    ];

    private array $highImpactKeywords = [
        'etf', 'sec', 'hack', 'ban', 'regulation', 'lawsuit',
        'exchange', 'bankruptcy', 'liquidation', 'crash', 'surge',
        'adoption', 'institutional', 'whale', 'government', 'fed',
        'interest rate', 'inflation', 'cpi', 'fomc', 'etf approval',
        'spot etf', 'blackrock', 'fidelity', 'ark invest',
    ];

    public function fetchAll(): array
    {
        $allNews = [];
        foreach ($this->sources as $source => $url) {
            $news = $this->fetchSource($source, $url);
            $allNews = array_merge($allNews, $news);
        }
        usort($allNews, fn($a, $b) => $b['published_at']->getTimestamp() - $a['published_at']->getTimestamp());
        return $allNews;
    }

    private function fetchSource(string $source, string $url): array
    {
        try {
            $response = Http::timeout(10)->get($url);
            if (!$response->ok()) return [];

            $xml = simplexml_load_string($response->body());
            if (!$xml) return [];

            $items = [];
            foreach ($xml->channel->item as $item) {
                $title = (string) $item->title;
                $link = (string) $item->link;
                $description = (string) ($item->description ?? '');
                $pubDate = isset($item->pubDate) ? \Carbon\Carbon::parse((string) $item->pubDate) : now();

                $items[] = [
                    'source' => $source,
                    'title' => $title,
                    'url' => $link,
                    'description' => strip_tags($description),
                    'published_at' => $pubDate,
                ];
            }
            return $items;
        } catch (\Throwable $e) {
            \Log::error("News fetch failed: {$source}", ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function detectHighImpact(string $title, string $description): array
    {
        $text = Str::lower($title . ' ' . $description);
        $found = [];
        foreach ($this->highImpactKeywords as $keyword) {
            if (Str::contains($text, $keyword)) {
                $found[] = $keyword;
            }
        }
        return $found;
    }

    public function storeNews(array $items): int
    {
        $stored = 0;
        foreach ($items as $item) {
            $keywords = $this->detectHighImpact($item['title'], $item['description'] ?? '');
            $highImpact = count($keywords) > 0;

            try {
                News::updateOrCreate(
                    ['url' => $item['url']],
                    [
                        'source' => $item['source'],
                        'title' => $item['title'],
                        'description' => $item['description'] ?? null,
                        'published_at' => $item['published_at'],
                        'high_impact' => $highImpact,
                        'impact_keywords' => $keywords,
                    ]
                );
                $stored++;
            } catch (\Throwable $e) {
                \Log::error("News store failed", ['url' => $item['url'], 'error' => $e->getMessage()]);
            }
        }
        return $stored;
    }

    public function refresh(): int
    {
        $items = $this->fetchAll();
        $stored = $this->storeNews($items);

        // Cache latest high impact news for real-time
        $highImpact = News::where('high_impact', true)
            ->latest('published_at')
            ->limit(10)
            ->get();
        Cache::put('news:high_impact', $highImpact, 300);

        return $stored;
    }

    public function getLatest(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return News::latest('published_at')->limit($limit)->get();
    }

    public function getHighImpact(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return News::where('high_impact', true)->latest('published_at')->limit($limit)->get();
    }
}
