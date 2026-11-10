<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user()->load('settings');
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'theme' => $user->settings->theme ?? 'light',
                'is_admin' => $user->is_admin ?? false,
            ],
            'api_health' => $this->checkApiHealth(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);
        $user->update($validated);
        return response()->json(['success' => true, 'user' => $user]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['error' => 'Password saat ini salah'], 422);
        }
        $user->update(['password' => Hash::make($validated['password'])]);
        return response()->json(['success' => true]);
    }

    public function updateTheme(Request $request): JsonResponse
    {
        $validated = $request->validate(['theme' => 'required|in:light,dark']);
        $user = $request->user();
        $user->settings()->updateOrCreate(
            ['user_id' => $user->id],
            ['theme' => $validated['theme']]
        );
        return response()->json(['success' => true, 'theme' => $validated['theme']]);
    }

    public function apiHealth(): JsonResponse
    {
        return response()->json($this->checkApiHealth());
    }

    // Admin only
    public function users(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $users = User::latest()->limit(5)->get(['id', 'name', 'email', 'is_admin', 'created_at']);
        return response()->json($users);
    }

    public function createUser(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => ['required', Password::defaults()],
            'is_admin' => 'boolean',
        ]);
        $user = User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);
        return response()->json(['success' => true, 'user' => $user], 201);
    }

    public function deleteUser(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);
        $user = User::findOrFail($id);
        if ($user->id === $request->user()->id) {
            return response()->json(['error' => 'Tidak bisa hapus diri sendiri'], 422);
        }
        $user->delete();
        return response()->json(['success' => true]);
    }

    private function authorizeAdmin(Request $request): void
    {
        if (!($request->user()->is_admin ?? false)) {
            abort(403, 'Admin only');
        }
    }

    private function checkApiHealth(): array
    {
        $checks = [];

        // 9router
        try {
            $start = microtime(true);
            $res = Http::timeout(5)->post(config('services.ai.base_url') . '/chat/completions', [
                'model' => config('services.ai.model'),
                'messages' => [['role' => 'user', 'content' => 'ping']],
            ]);
            $checks['9router'] = [
                'status' => $res->ok() ? 'healthy' : 'error',
                'latency_ms' => round((microtime(true) - $start) * 1000),
            ];
        } catch (\Throwable $e) {
            $checks['9router'] = ['status' => 'error', 'error' => $e->getMessage()];
        }

        // CoinGecko
        try {
            $start = microtime(true);
            $res = Http::timeout(5)->get('https://api.coingecko.com/api/v3/ping');
            $checks['coingecko'] = [
                'status' => $res->ok() ? 'healthy' : 'error',
                'latency_ms' => round((microtime(true) - $start) * 1000),
            ];
        } catch (\Throwable $e) {
            $checks['coingecko'] = ['status' => 'error', 'error' => $e->getMessage()];
        }

        // Binance
        try {
            $start = microtime(true);
            $res = Http::timeout(5)->get('https://api.binance.com/api/v3/ping');
            $checks['binance'] = [
                'status' => $res->ok() ? 'healthy' : 'error',
                'latency_ms' => round((microtime(true) - $start) * 1000),
            ];
        } catch (\Throwable $e) {
            $checks['binance'] = ['status' => 'error', 'error' => $e->getMessage()];
        }

        return $checks;
    }
}
