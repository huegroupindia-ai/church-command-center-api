<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * List user's conversations
     */
    public function index(Request $request)
    {
        $userId = Auth::guard('api')->id();
        
        $conversations = Conversation::whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->with(['lastMessage.user', 'participants'])
        ->withCount('messages')
        ->orderByDesc('updated_at')
        ->get()
        ->map(function ($conv) use ($userId) {
            $conv->unread_count = $conv->unread_count;
            $conv->other_participant = $conv->otherParticipant;
            $conv->display_name = $conv->display_name;
            return $conv;
        });

        return response()->json([
            'success' => true,
            'data' => $conversations,
        ]);
    }

    /**
     * Create a new conversation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,id',
        ]);

        $userId = Auth::guard('api')->id();

        // For 1:1 conversations, check if one already exists
        if (count($validated['participant_ids']) === 1 && empty($validated['name'])) {
            $otherId = $validated['participant_ids'][0];
            
            $existing = Conversation::where('is_group', false)
                ->whereHas('participants', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->whereHas('participants', function ($q) use ($otherId) {
                    $q->where('user_id', $otherId);
                })
                ->withCount('messages')
                ->where('messages_count', 0)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => true,
                    'data' => $existing->load(['lastMessage.user', 'participants']),
                ]);
            }
        }

        $conversation = Conversation::create([
            'name' => $validated['name'] ?? null,
            'is_group' => count($validated['participant_ids']) > 1 || !empty($validated['name']),
            'created_by' => $userId,
        ]);

        // Add creator as participant
        $conversation->participants()->attach($userId);

        // Add other participants
        foreach ($validated['participant_ids'] as $pid) {
            $conversation->participants()->attach($pid);
        }

        return response()->json([
            'success' => true,
            'data' => $conversation->load(['lastMessage.user', 'participants']),
        ], 201);
    }

    /**
     * Get conversation with messages
     */
    public function show($id)
    {
        $userId = Auth::guard('api')->id();

        $conversation = Conversation::where('id', $id)
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with(['participants', 'creator'])
            ->firstOrFail();

        // Mark as read
        $conversation->participants()
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);

        $messages = $conversation->messages()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation,
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request, $id)
    {
        $validated = $request->validate([
            'body' => 'required|string|max:5000',
            'type' => 'nullable|string|in:text,image,file,system',
        ]);

        $userId = Auth::guard('api')->id();

        $conversation = Conversation::where('id', $id)
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->firstOrFail();

        $message = Message::create([
            'conversation_id' => $id,
            'user_id' => $userId,
            'body' => $validated['body'],
            'type' => $validated['type'] ?? 'text',
        ]);

        // Update conversation timestamp
        $conversation->touch();

        // Load user data
        $message->load('user');

        return response()->json([
            'success' => true,
            'data' => $message,
        ], 201);
    }

    /**
     * Get messages for a conversation (paginated)
     */
    public function messages(Request $request, $id)
    {
        $userId = Auth::guard('api')->id();

        $conversation = Conversation::where('id', $id)
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->firstOrFail();

        $messages = $conversation->messages()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Mark conversation as read
     */
    public function markRead($id)
    {
        $userId = Auth::guard('api')->id();

        Conversation::where('id', $id)
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->firstOrFail()
            ->participants()
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete a conversation
     */
    public function destroy($id)
    {
        $userId = Auth::guard('api')->id();

        $conversation = Conversation::where('id', $id)
            ->where('created_by', $userId)
            ->firstOrFail();

        $conversation->delete();

        return response()->json(['success' => true]);
    }
}
