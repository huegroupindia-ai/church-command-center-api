<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::guard('api')->id();
        $notifications = AppNotification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($n) => $this->format($n));

        return response()->json(['data' => $notifications]);
    }

    public function show($id)
    {
        return response()->json($this->format(AppNotification::findOrFail($id)));
    }

    public function markAsRead($id)
    {
        $n = AppNotification::findOrFail($id);
        $n->update(['read_at' => now()]);

        return response()->json($this->format($n));
    }

    public function markAllAsRead()
    {
        $userId = Auth::guard('api')->id();
        AppNotification::where('user_id', $userId)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function destroy($id)
    {
        AppNotification::findOrFail($id)->delete();

        return response()->json(['message' => 'Notification deleted']);
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'title' => ['required', 'string'],
            'message' => ['required', 'string'],
            'type' => ['nullable', 'string'],
            'data' => ['nullable', 'array'],
        ]);

        $n = AppNotification::create([
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'message' => $data['message'],
            'type' => $data['type'] ?? 'general',
            'data' => $data['data'] ?? null,
        ]);

        return response()->json($this->format($n), 201);
    }

    public function sendDepartment(Request $request)
    {
        $request->validate([
            'department_id' => ['required', 'integer'],
            'title' => ['required', 'string'],
            'message' => ['required', 'string'],
        ]);

        return response()->json(['message' => 'Department notification sent']);
    }

    public function serviceReminder($serviceId)
    {
        return response()->json(['message' => 'Service reminder sent', 'service_id' => (int) $serviceId]);
    }

    public function removeToken()
    {
        return response()->json(['message' => 'Token removed']);
    }

    protected function format(AppNotification $n): array
    {
        return [
            'id' => $n->id,
            'user_id' => $n->user_id,
            'type' => $n->type,
            'title' => $n->title,
            'message' => $n->message,
            'data' => $n->data,
            'read_at' => $n->read_at?->toIso8601String(),
            'created_at' => $n->created_at?->toIso8601String(),
            'updated_at' => $n->updated_at?->toIso8601String(),
        ];
    }
}
