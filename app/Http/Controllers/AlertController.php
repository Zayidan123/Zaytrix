<?php
namespace App\Http\Controllers;

use App\Models\AlertConfig;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $alerts = AlertConfig::where('user_id', $request->user()->id)->latest()->get();
        return response()->json($alerts);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => 'required|string|max:10',
            'condition' => 'required|in:above,below',
            'target_price' => 'required|numeric|min:0',
        ]);

        $alert = AlertConfig::create([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);
        return response()->json(['success' => true, 'alert' => $alert], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $alert = AlertConfig::where('user_id', $request->user()->id)->where('id', $id)->first();
        if (!$alert) {
            return response()->json(['error' => 'Not found'], 404);
        }
        $alert->delete();
        return response()->json(['success' => true]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->latest()
            ->limit(50)
            ->get();
        return response()->json($notifications);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $notification = Notification::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();
        if ($notification) {
            $notification->update(['read' => true]);
        }
        return response()->json(['success' => true]);
    }
}
