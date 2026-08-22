<?php

namespace App\Http\Controllers\Api;

use App\Events\ChecklistUpdated;
use App\Http\Controllers\Controller;
use App\Models\ServiceChecklistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

class ServiceChecklistItemController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceChecklistItem::query();
        if ($request->filled('checklist_id')) {
            $query->where('checklist_id', $request->query('checklist_id'));
        }

        return response()->json(['data' => $query->orderBy('sort_order')->get()->map(fn ($i) => $this->formatItem($i))]);
    }

    public function show($id)
    {
        return response()->json($this->formatItem(ServiceChecklistItem::with('evidence')->findOrFail($id)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'checklist_id' => ['required', 'integer'],
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'verification_type' => ['nullable', 'string'],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $item = ServiceChecklistItem::create(array_merge($data, ['status' => 'pending']));

        return response()->json($this->formatItem($item), 201);
    }

    public function update(Request $request, $id)
    {
        $item = ServiceChecklistItem::findOrFail($id);
        $item->update($request->only(['status', 'title', 'description']));

        return response()->json($this->formatItem($item));
    }

    public function complete($id)
    {
        $item = ServiceChecklistItem::findOrFail($id);
        $item->update([
            'status' => 'completed',
            'completed_by' => Auth::guard('api')->id(),
            'completed_at' => now(),
        ]);

        $this->broadcastChecklistUpdate($item);

        return response()->json($this->formatItem($item));
    }

    public function verify(Request $request, $id)
    {
        $item = ServiceChecklistItem::findOrFail($id);
        $item->update([
            'status' => 'verified',
            'verified_by' => Auth::guard('api')->id(),
            'verified_at' => now(),
        ]);

        $this->broadcastChecklistUpdate($item);

        return response()->json($this->formatItem($item));
    }

    public function approve(Request $request, $id)
    {
        $item = ServiceChecklistItem::findOrFail($id);
        $item->update([
            'status' => 'approved',
            'approved_by' => Auth::guard('api')->id(),
            'approved_at' => now(),
        ]);

        $this->broadcastChecklistUpdate($item);

        return response()->json($this->formatItem($item));
    }

    public function reject(Request $request, $id)
    {
        $item = ServiceChecklistItem::findOrFail($id);
        $item->update(['status' => 'rejected']);

        return response()->json($this->formatItem($item));
    }

    public function destroy($id)
    {
        ServiceChecklistItem::findOrFail($id)->delete();

        broadcast(new \App\Events\DashboardRefresh(reason: 'checklist_item_deleted'));

        return response()->json(['message' => 'Item deleted']);
    }

    public function formatItem(ServiceChecklistItem $i): array
    {
        return [
            'id' => $i->id,
            'checklist_id' => $i->checklist_id,
            'template_item_id' => $i->template_item_id,
            'title' => $i->title,
            'description' => $i->description,
            'verification_type' => $i->verification_type ?? 'none',
            'is_required' => (bool) $i->is_required,
            'status' => $i->status ?? 'pending',
            'completed_by' => $i->completed_by,
            'completed_at' => $i->completed_at?->toIso8601String(),
            'verified_by' => $i->verified_by,
            'verified_at' => $i->verified_at?->toIso8601String(),
            'approved_by' => $i->approved_by,
            'approved_at' => $i->approved_at?->toIso8601String(),
            'sort_order' => $i->sort_order ?? 0,
            'created_at' => $i->created_at?->toIso8601String(),
            'updated_at' => $i->updated_at?->toIso8601String(),
            'evidence' => $i->relationLoaded('evidence') ? $i->evidence->map(fn ($e) => [
                'id' => $e->id,
                'checklist_item_id' => $e->checklist_item_id,
                'user_id' => $e->user_id,
                'type' => $e->type,
                'file_path' => $e->file_path,
                'file_name' => $e->file_name,
                'file_size' => $e->file_size,
                'mime_type' => $e->mime_type,
                'notes' => $e->notes,
                'created_at' => $e->created_at?->toIso8601String(),
                'updated_at' => $e->updated_at?->toIso8601String(),
            ])->values() : [],
        ];
    }

    protected function broadcastChecklistUpdate(ServiceChecklistItem $item): void
    {
        $checklist = $item->checklist;
        $totalItems = $checklist ? $checklist->items()->count() : 0;
        $completedItems = $checklist ? $checklist->items()->whereIn('status', ['completed', 'verified', 'approved'])->count() : 0;
        $percentage = $totalItems > 0 ? round(($completedItems / $totalItems) * 100) : 0;

        broadcast(new ChecklistUpdated([
            'checklist_id' => $item->checklist_id,
            'service_id' => $checklist?->service_id,
            'item_name' => $item->title,
            'completed_by' => $item->completed_by ? \App\Models\User::find($item->completed_by)?->name : '',
            'completion_percentage' => $percentage,
        ]));

        broadcast(new \App\Events\DashboardRefresh(
            userId: Auth::guard('api')->id(),
            reason: 'checklist_updated',
        ));
    }
}
