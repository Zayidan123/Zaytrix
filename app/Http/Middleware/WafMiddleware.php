<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WafMiddleware
{
    private array $xssPatterns = [
        '/<script/i', '/javascript:/i', '/on\w+=/i', '/<iframe/i',
        '/<img.*onerror/i', '/<svg.*onload/i', '/expression\s*\(/i',
        '/alert\s*\(/i', '/document\.cookie/i', '/\.\.\//', '/union\s+select/i',
    ];

    private array $sqlPatterns = [
        '/union\s+select/i', '/select\s+.*\s+from/i', '/drop\s+table/i',
        '/insert\s+into/i', '/delete\s+from/i', '/--\s/', "/'\s*or\s*1\s*=\s*1/i",
        '/exec\s*\(/i', '/xp_cmdshell/i',
    ];

    public function handle(Request $request, Closure $next)
    {
        $input = json_encode($request->all());
        foreach ($this->xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return response()->json(['error' => 'XSS attack detected'], 400);
            }
        }
        foreach ($this->sqlPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return response()->json(['error' => 'SQL injection detected'], 400);
            }
        }
        return $next($request);
    }
}
