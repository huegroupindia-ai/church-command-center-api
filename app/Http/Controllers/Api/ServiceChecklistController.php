<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChecklistTemplate;
use App\Models\ServiceChecklist;
use Illuminate\Http\Request;

class ServiceChecklistController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceChecklist::query()->with('items');
        if ($request->filled('service_id')) {
            $query->where('service_id', $request->query('service_id'));
        }

        return response()->json(['data' => $query->get()->map(fn ($c) => $this->format($c))]);
    }

    public function show($id)
    {
        return response()->json($this->format(ServiceChecklist::with('items')->findOrFail($id)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer'],
            'template_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'assigned_to' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);

        $checklist = ServiceChecklist::create([
            'service_id' => $data['service_id'],
            'template_id' => $data['template_id'] ?? null,
            'department_id' => $data['department_id'] ?? 1,
            'assigned_to' => $data['assigned_to'] ?? null,
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);

        if (! empty($data['template_id'])) {
            $template = ChecklistTemplate::with('items')->find($data['template_id']);
            if ($template) {
                foreach ($template->items as $item) {
                    $checklist->items()->create([
                        'template_item_id' => $item->id,
                        'title' => $item->title,
                        'description' => $item->description,
                        'verification_type' => $item->verification_type,
                        'is_required' => $item->is_required,
                        'status' => 'pending',
                        'sort_order' => $item->sort_order,
                    ]);
                }
            }
        }

        return response()->json($this->format($checklist->load('items')), 201);
    }

    public function update(Request $request, $id)
    {
        $checklist = ServiceChecklist::findOrFail($id);
        $checklist->update($request->only(['status', 'notes', 'assigned_to']));

        return response()->json($this->format($checklist->load('items')));
    }

    public function destroy($id)
    {
        $checklist = ServiceChecklist::findOrFail($id);
        $checklist->items()->delete();
        $checklist->delete();

        return response()->json(['message' => 'Checklist deleted']);
    }

    protected function format(ServiceChecklist $c): array
    {
        return [
            'id' => $c->id,
            'service_id' => $c->service_id,
            'template_id' => $c->template_id,
            'department_id' => $c->department_id,
            'assigned_to' => $c->assigned_to,
            'status' => $c->status ?? 'pending',
            'completed_at' => $c->completed_at?->toIso8601String(),
            'verified_at' => $c->verified_at?->toIso8601String(),
            'approved_at' => $c->approved_at?->toIso8601String(),
            'verified_by' => $c->verified_by,
            'approved_by' => $c->approved_by,
            'notes' => $c->notes,
            'created_at' => $c->created_at?->toIso8601String(),
            'updated_at' => $c->updated_at?->toIso8601String(),
            'items' => $c->relationLoaded('items') ? $c->items->map(fn ($i) => (new ServiceChecklistItemController)->formatItem($i))->values() : [],
        ];
    }
}
