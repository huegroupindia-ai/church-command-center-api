<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EquipmentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EquipmentCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = EquipmentCategory::query()->orderBy('name', 'asc');

        if ($request->filled('church_id')) {
            $query->where('church_id', $request->query('church_id'));
        }

        return response()->json(['data' => $query->get()->map(fn ($c) => $this->format($c))]);
    }

    public function store(Request $request)
    {
        $data = $this->validateCategory($request);
        $data['created_by'] = Auth::guard('api')->id() ?? 1;
        $data['church_id'] = $data['church_id'] ?? 1;

        $category = EquipmentCategory::create($data);

        return response()->json($this->format($category), 201);
    }

    public function show($id)
    {
        $category = EquipmentCategory::findOrFail($id);

        return response()->json($this->format($category));
    }

    public function update(Request $request, $id)
    {
        $category = EquipmentCategory::findOrFail($id);
        $data = $this->validateCategory($request, false);
        $category->update($data);

        return response()->json($this->format($category));
    }

    public function destroy($id)
    {
        EquipmentCategory::findOrFail($id)->delete();

        return response()->json(['message' => 'Equipment category deleted']);
    }

    protected function validateCategory(Request $request, bool $required = true): array
    {
        $rule = $required ? 'required' : 'sometimes';

        return $request->validate([
            'church_id' => ['nullable', 'integer'],
            'name' => [$rule, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
        ]);
    }

    protected function format(EquipmentCategory $c): array
    {
        return [
            'id' => $c->id,
            'church_id' => $c->church_id ?? 1,
            'name' => $c->name,
            'description' => $c->description,
            'icon' => $c->icon,
            'created_at' => $c->created_at?->toIso8601String(),
            'updated_at' => $c->updated_at?->toIso8601String(),
            'equipment' => $c->equipment->map(fn ($e) => $this->formatEquipment($e))->all(),
        ];
    }

    protected function formatEquipment($e): array
    {
        return [
            'id' => $e->id,
            'church_id' => $e->church_id ?? 1,
            'category_id' => $e->category_id,
            'department_id' => $e->department_id,
            'name' => $e->name,
            'asset_id' => $e->asset_id,
            'description' => $e->description,
            'brand' => $e->brand,
            'model' => $e->model,
            'serial_number' => $e->serial_number,
            'purchase_date' => $e->purchase_date?->toDateString(),
            'warranty_expires_at' => $e->warranty_expires_at?->toDateString(),
            'purchase_price' => $e->purchase_price,
            'status' => $e->status ?? 'active',
            'qr_code' => $e->qr_code,
            'qr_code_image_path' => $e->qr_code_image_path,
            'location' => $e->location,
            'last_maintenance_at' => $e->last_maintenance_at?->toDateString(),
            'next_maintenance_at' => $e->next_maintenance_at?->toDateString(),
            'image_path' => $e->image_path,
            'created_at' => $e->created_at?->toIso8601String(),
            'updated_at' => $e->updated_at?->toIso8601String(),
        ];
    }
}

