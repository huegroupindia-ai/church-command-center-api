<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateItem;
use Illuminate\Http\Request;

class ChecklistTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = ChecklistTemplate::query()->with('items');
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->query('department_id'));
        }

        return response()->json(['data' => $query->get()->map(fn ($t) => $this->format($t))]);
    }

    public function show($id)
    {
        return response()->json($this->format(ChecklistTemplate::with('items')->findOrFail($id)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'church_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'is_global' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
        ]);

        $template = ChecklistTemplate::create([
            'church_id' => $data['church_id'] ?? 1,
            'department_id' => $data['department_id'] ?? 1,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? 'general',
            'is_global' => $data['is_global'] ?? false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        foreach ($request->input('items', []) as $i => $item) {
            $template->items()->create([
                'title' => $item['title'] ?? 'Item',
                'description' => $item['description'] ?? null,
                'verification_type' => $item['verification_type'] ?? 'none',
                'is_required' => $item['is_required'] ?? true,
                'sort_order' => $item['sort_order'] ?? $i,
                'estimated_minutes' => $item['estimated_minutes'] ?? null,
            ]);
        }

        return response()->json($this->format($template->load('items')), 201);
    }

    public function update(Request $request, $id)
    {
        $template = ChecklistTemplate::findOrFail($id);
        $template->update($request->only([
            'name', 'description', 'category', 'is_global', 'is_active', 'department_id',
        ]));

        return response()->json($this->format($template->load('items')));
    }

    public function destroy($id)
    {
        $template = ChecklistTemplate::findOrFail($id);
        $template->items()->delete();
        $template->delete();

        return response()->json(['message' => 'Template deleted']);
    }

    protected function format(ChecklistTemplate $t): array
    {
        return [
            'id' => $t->id,
            'church_id' => $t->church_id,
            'department_id' => $t->department_id,
            'name' => $t->name,
            'description' => $t->description,
            'category' => $t->category ?? 'general',
            'is_global' => (bool) $t->is_global,
            'is_active' => (bool) $t->is_active,
            'created_at' => $t->created_at?->toIso8601String(),
            'updated_at' => $t->updated_at?->toIso8601String(),
            'items' => $t->relationLoaded('items') ? $t->items->map(fn ($i) => [
                'id' => $i->id,
                'template_id' => $i->template_id,
                'title' => $i->title,
                'description' => $i->description,
                'verification_type' => $i->verification_type ?? 'none',
                'is_required' => (bool) $i->is_required,
                'sort_order' => $i->sort_order ?? 0,
                'estimated_minutes' => $i->estimated_minutes,
                'created_at' => $i->created_at?->toIso8601String(),
                'updated_at' => $i->updated_at?->toIso8601String(),
            ])->values() : [],
        ];
    }
}
