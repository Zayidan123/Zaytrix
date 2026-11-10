<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SsrfGuardMiddleware
{
    private array $blockedHosts = [
        'localhost', '127.0.0.1', '0.0.0.0', '::1',
        '169.254.169.254', 'metadata.google.internal',
        '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16',
    ];

    public function handle(Request $request, Closure $next)
    {
        $urls = [];
        foreach ($request->all() as $value) {
            if (is_string($value) && preg_match('/https?:\/\//i', $value)) {
                $urls[] = $value;
            }
        }
        foreach ($urls as $url) {
            $host = parse_url($url, PHP_URL_HOST);
            if ($host && $this->isBlocked($host)) {
                return response()->json(['error' => 'SSRF blocked'], 403);
            }
        }
        return $next($request);
    }

    private function isBlocked(string $host): bool
    {
        if (in_array($host, $this->blockedHosts)) return true;
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false) return true;
        return false;
    }
}
