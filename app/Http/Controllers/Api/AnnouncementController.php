<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();
        $query = Announcement::active()
            ->where('church_id', $user->church_id)
            ->with('author:id,name,avatar');

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        $announcements = $query->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($announcements);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('api')->user();
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'category' => 'nullable|string|max:100',
            'priority' => 'nullable|in:low,medium,high',
            'is_pinned' => 'nullable|boolean',
            'expires_at' => 'nullable|date',
        ]);

        $announcement = Announcement::create([
            'church_id' => $user->church_id,
            'author_id' => $user->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'category' => $validated['category'] ?? 'general',
            'priority' => $validated['priority'] ?? 'medium',
            'is_pinned' => $validated['is_pinned'] ?? false,
            'published_at' => now(),
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return response()->json(['data' => $announcement->load('author:id,name,avatar')], 201);
    }

    public function show($id)
    {
        $user = Auth::guard('api')->user();
        $announcement = Announcement::where('church_id', $user->church_id)
            ->with('author:id,name,avatar')
            ->findOrFail($id);

        return response()->json(['data' => $announcement]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::guard('api')->user();
        $announcement = Announcement::where('church_id', $user->church_id)->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
            'category' => 'nullable|string|max:100',
            'priority' => 'nullable|in:low,medium,high',
            'is_pinned' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'expires_at' => 'nullable|date',
        ]);

        $announcement->update($validated);

        return response()->json(['data' => $announcement->load('author:id,name,avatar')]);
    }

    public function destroy($id)
    {
        $user = Auth::guard('api')->user();
        $announcement = Announcement::where('church_id', $user->church_id)->findOrFail($id);
        $announcement->delete();

        return response()->json(['message' => 'Announcement deleted']);
    }
}
