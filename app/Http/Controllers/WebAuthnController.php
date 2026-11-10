<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WebauthnCredential;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebAuthnController extends Controller
{
    private string $rpOrigin;
    private string $rpId;

    public function __construct()
    {
        $this->rpOrigin = config('app.url', 'http://localhost:3000');
        $this->rpId = parse_url($this->rpOrigin, PHP_URL_HOST) ?? 'localhost';
    }

    public function registrationOptions(Request $request): JsonResponse
    {
        $user = $request->user();
        try {
            $publicKeyCredentialCreationOptions = $this->generatePublicKeyCredentialCreationOptions($user);
            return response()->json($publicKeyCredentialCreationOptions);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeCredential(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'credential_id' => 'required|string',
            'public_key' => 'required|string',
            'attestation' => 'required|array',
            'name' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        try {
            $credential = WebauthnCredential::create([
                'user_id' => $user->id,
                'credential_id' => $validated['credential_id'],
                'public_key' => $validated['public_key'],
                'name' => $validated['name'] ?? 'Security Key',
                'counter' => 0,
                'transports' => $validated['attestation']['transports'] ?? null,
            ]);
            return response()->json(['success' => true, 'credential' => $credential]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function authenticationOptions(Request $request): JsonResponse
    {
        try {
            $options = $this->generatePublicKeyRequestOptions();
            return response()->json($options);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function authenticate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'credential_id' => 'required|string',
            'signature' => 'required|string',
            'client_data' => 'required|array',
            'auth_data' => 'required|string',
        ]);

        try {
            $credential = WebauthnCredential::where('credential_id', $validated['credential_id'])->first();
            if (!$credential) {
                return response()->json(['error' => 'Credential not found'], 404);
            }

            $credential->update(['last_used_at' => now()]);
            $user = $credential->user;

            $token = $user->createToken('webauthn-auth')->plainTextToken;
            return response()->json(['success' => true, 'token' => $token, 'user' => $user]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listCredentials(Request $request): JsonResponse
    {
        $credentials = $request->user()->webauthnCredentials;
        return response()->json($credentials);
    }

    public function deleteCredential(Request $request, int $id): JsonResponse
    {
        $credential = WebauthnCredential::where('user_id', $request->user()->id)->findOrFail($id);
        $credential->delete();
        return response()->json(['success' => true]);
    }

    private function generatePublicKeyCredentialCreationOptions(User $user): array
    {
        $challenge = Str::random(32);
        Cache::put("webauthn:challenge:{$user->id}", $challenge, 300);

        return [
            'challenge' => base64_encode($challenge),
            'rp' => ['name' => 'ZAYTRIX', 'id' => $this->rpId],
            'user' => ['id' => base64_encode((string) $user->id), 'name' => $user->email, 'displayName' => $user->name],
            'pubKeyCredParams' => [['type' => 'public-key', 'alg' => -7], ['type' => 'public-key', 'alg' => -257]],
            'timeout' => 60000,
            'excludeCredentials' => $user->webauthnCredentials->map(fn($c) => ['type' => 'public-key', 'id' => $c->credential_id]),
            'supportedTransport' => ['hybrid', 'usb', 'nfc', 'ble', 'internal'],
        ];
    }

    private function generatePublicKeyRequestOptions(): array
    {
        $challenge = Str::random(32);
        Cache::put("webauthn:challenge:auth", $challenge, 300);

        return [
            'challenge' => base64_encode($challenge),
            'timeout' => 60000,
            'rpId' => $this->rpId,
            'allowCredentials' => [],
        ];
    }
}
