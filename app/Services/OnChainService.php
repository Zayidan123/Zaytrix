<?php
namespace App\Services;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OnChainService
{
    private string $base = 'https://mempool.space/api';

    public function btcPrice(): array
    {
        return Cache::remember('onchain:btcprice', 60, function () {
            $r = Http::timeout(10)->get("{$this->base}/v1/prices");
            return $r->successful() ? ($r->json() ?? []) : [];
        });
    }

    public function difficultyAdjustment(): array
    {
        return Cache::remember('onchain:difficulty', 120, function () {
            $r = Http::timeout(10)->get("{$this->base}/v1/difficulty-adjustment");
            return $r->successful() ? ($r->json() ?? []) : [];
        });
    }

    public function recentBlocks(int $limit = 15): array
    {
        return Cache::remember('onchain:blocks', 60, function () use ($limit) {
            $r = Http::timeout(10)->get("{$this->base}/v1/blocks");
            $data = $r->successful() ? ($r->json() ?? []) : [];
            return array_slice($data, 0, $limit);
        });
    }

    public function fees(): array
    {
        return Cache::remember('onchain:fees', 60, function () {
            $r = Http::timeout(10)->get("{$this->base}/v1/fees/recommended");
            return $r->successful() ? ($r->json() ?? []) : [];
        });
    }

    public function mempoolBlocks(): array
    {
        return Cache::remember('onchain:mempool-blocks', 60, function () {
            // Endpoint ini kadang butut timeout lebih panjang (data besar).
            $r = Http::timeout(15)->get("{$this->base}/v1/fees/mempool-blocks");
            return $r->successful() ? ($r->json() ?? []) : [];
        });
    }

    public function miningPools(string $period = '1w'): array
    {
        return Cache::remember("onchain:pools:{$period}", 300, function () use ($period) {
            $r = Http::timeout(10)->get("{$this->base}/v1/mining/pools/{$period}");
            return $r->successful() ? ($r->json() ?? []) : [];
        });
    }

    public function hashrate(string $period = '3d'): array
    {
        return Cache::remember("onchain:hashrate:{$period}", 300, function () use ($period) {
            $r = Http::timeout(10)->get("{$this->base}/v1/mining/hashrate/{$period}");
            return $r->successful() ? ($r->json() ?? []) : [];
        });
    }

    public function difficultyAdjustments(string $period = '1w'): array
    {
        return Cache::remember("onchain:difficulty-adjustments:{$period}", 300, function () use ($period) {
            $r = Http::timeout(10)->get("{$this->base}/v1/mining/difficulty-adjustments/{$period}");
            return $r->successful() ? ($r->json() ?? []) : [];
        });
    }

    public function summary(): array
    {
        return [
            'price' => $this->btcPrice(),
            'difficulty' => $this->difficultyAdjustment(),
            'blocks' => $this->recentBlocks(),
            'fees' => $this->fees(),
            'mempool_blocks' => $this->mempoolBlocks(),
            'pools' => $this->miningPools(),
            'hashrate' => $this->hashrate(),
            'difficulty_adjustments' => $this->difficultyAdjustments(),
        ];
    }
}
