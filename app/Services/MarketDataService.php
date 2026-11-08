<?php
namespace App\Services;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MarketDataService
{
    public function getTopCoins(int $limit = 500): array
    {
        $limit = min(max($limit, 1), 500);
        return Cache::remember("market:top:$limit", 60, function () use ($limit) {
            $coins = [];
            $perPage = 250;
            $pages = (int) ceil($limit / $perPage);
            for ($page = 1; $page <= $pages; $page++) {
                $remaining = $limit - count($coins);
                $resp = Http::timeout(15)->get('https://api.coingecko.com/api/v3/coins/markets', [
                    'vs_currency' => 'usd',
                    'order' => 'market_cap_desc',
                    'per_page' => min($perPage, $remaining),
                    'page' => $page,
                    'sparkline' => 'false',
                ]);
                if (!$resp->successful()) break;
                $data = $resp->json();
                if (empty($data)) break;
                foreach ($data as $c) $coins[] = $c;
                if (count($coins) >= $limit) break;
            }
            return array_slice($coins, 0, $limit);
        });
    }

    public function getGlobal(): array
    {
        return Cache::remember('market:global', 120, function () {
            $resp = Http::timeout(15)->get('https://api.coingecko.com/api/v3/global');
            return $resp->successful() ? ($resp->json()['data'] ?? []) : [];
        });
    }
}
