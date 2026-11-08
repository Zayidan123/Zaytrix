<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WhaleRadarService
{
    // Bounded-duration Binance trade stream: connect ~8s, collect large trades, disconnect.
    public function fetchWhales(string $symbol = 'BTCUSDT', int $windowMs = 8000, float $whaleUsd = 50000): array
    {
        $sock = @stream_socket_client('ssl://data-stream.binance.com:9443', $errno, $errstr, 8);
        if (!$sock) {
            // Fallback: mempool.space mempool-blocks (on-chain proxy for large txs)
            return $this->fallbackFromMempool($whaleUsd);
        }
        stream_set_timeout($sock, 4, $windowMs * 1000);
        $payload = json_encode(['method' => 'SUBSCRIBE', 'params' => ["{$symbol}@trade"], 'id' => 1]);
        fwrite($sock, $payload . "\n");
        $deadline = hrtime(true) / 1e6 + $windowMs;
        $whales = [];
        $lastPrice = 0;
        while (hrtime(true) / 1e6 < $deadline) {
            $line = fgets($sock);
            if ($line === false || $line === '') break;
            $line = trim($line);
            if ($line === '' || $line[0] !== '{') continue;
            $msg = json_decode($line, true);
            if (!isset($msg['p'])) continue;
            $price = (float)$msg['p'];
            $qty = (float)$msg['q'];
            $usd = $price * $qty;
            if ($usd >= $whaleUsd) {
                $lastPrice = $price;
                $whales[] = [
                    't' => $msg['T'] ?? null,
                    'side' => $msg['m'] ? 'SELL' : 'BUY',
                    'price' => $price,
                    'qty' => $qty,
                    'usd' => $usd,
                ];
            }
            if (count($whales) >= 50) break;
        }
        @stream_socket_shutdown($sock, STREAM_SHUT_RDWR);
        @fclose($sock);
        $result = ['whales' => $whales, 'last_price' => $lastPrice, 'captured_at' => now()->toIso8601String(), 'source' => 'binance'];
        Cache::put('whale:latest', $result, 15);
        return $result;
    }

    // Fallback: ambil block mempool dengan totalFees tinggi sebagai proxy whale activity
    private function fallbackFromMempool(float $whaleUsd): array
    {
        $r = Http::timeout(15)->get('https://mempool.space/api/v1/fees/mempool-blocks');
        if (!$r->successful()) {
            return ['error' => 'Binance & mempool blocked', 'whales' => [], 'last_price' => null, 'captured_at' => now()->toIso8601String(), 'source' => 'none'];
        }
        $blocks = $r->json() ?? [];
        usort($blocks, fn($a, $b) => ($b['totalFees'] ?? 0) <=> ($a['totalFees'] ?? 0));
        $whales = [];
        foreach ($blocks as $b) {
            $feeBtc = ($b['totalFees'] ?? 0) / 1e8;
            $whales[] = [
                't' => time(),
                'side' => 'BLOCK',
                'price' => null,
                'qty' => $feeBtc,
                'usd' => $feeBtc * ($b['medianFee'] ?? 1) * 1e8,
            ];
            if (count($whales) >= 10) break;
        }
        $result = ['whales' => $whales, 'last_price' => null, 'captured_at' => now()->toIso8601String(), 'source' => 'mempool'];
        Cache::put('whale:latest', $result, 15);
        return $result;
    }
}
