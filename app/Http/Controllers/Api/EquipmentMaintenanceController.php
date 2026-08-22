<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EquipmentMaintenanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EquipmentMaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $query = EquipmentMaintenanceLog::query()->orderBy('performed_at', 'desc');

        if ($request->filled('equipment_id')) {
            $query->where('equipment_id', $request->query('equipment_id'));
        }

        return response()->json([
            'data' => $query->get()->map(fn ($m) => $this->format($m)),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateMaintenance($request);
        $data['performed_by'] = $data['performed_by'] ?? (Auth::guard('api')->id() ?? 1);

        $log = EquipmentMaintenanceLog::create($data);

        return response()->json($this->format($log), 201);
    }

    public function show($id)
    {
        $log = EquipmentMaintenanceLog::findOrFail($id);

        return response()->json($this->format($log));
    }

    public function update(Request $request, $id)
    {
        $log = EquipmentMaintenanceLog::findOrFail($id);
        $data = $this->validateMaintenance($request, false);
        $log->update($data);

        return response()->json($this->format($log));
    }

    public function destroy($id)
    {
        EquipmentMaintenanceLog::findOrFail($id)->delete();

        return response()->json(['message' => 'Maintenance log deleted']);
    }

    protected function validateMaintenance(Request $request, bool $required = true): array
    {
        $rule = $required ? 'required' : 'sometimes';

        return $request->validate([
            'equipment_id' => [$rule, 'integer'],
            'performed_by' => ['nullable', 'integer'],
            'type' => [$rule, 'string', 'max:255'],
            'description' => [$rule, 'string'],
            'cost' => ['nullable', 'numeric'],
            'performed_at' => [$rule, 'date'],
            'next_maintenance_at' => ['nullable', 'date'],
        ]);
    }

    protected function format(EquipmentMaintenanceLog $m): array
    {
        return [
            'id' => $m->id,
            'equipment_id' => $m->equipment_id,
            'performed_by' => $m->performed_by,
            'type' => $m->type,
            'description' => $m->description,
            'cost' => $m->cost,
            'performed_at' => $m->performed_at?->toDateString(),
            'next_maintenance_at' => $m->next_maintenance_at?->toDateString(),
            'created_at' => $m->created_at?->toIso8601String(),
            'updated_at' => $m->updated_at?->toIso8601String(),
            'equipment' => $m->equipment ? [
                'id' => $m->equipment->id,
                'church_id' => $m->equipment->church_id ?? 1,
                'category_id' => $m->equipment->category_id,
                'department_id' => $m->equipment->department_id,
                'name' => $m->equipment->name,
                'asset_id' => $m->equipment->asset_id,
                'description' => $m->equipment->description,
                'brand' => $m->equipment->brand,
                'model' => $m->equipment->model,
                'serial_number' => $m->equipment->serial_number,
                'purchase_date' => $m->equipment->purchase_date?->toDateString(),
                'warranty_expires_at' => $m->equipment->warranty_expires_at?->toDateString(),
                'purchase_price' => $m->equipment->purchase_price,
                'status' => $m->equipment->status ?? 'active',
                'qr_code' => $m->equipment->qr_code,
                'qr_code_image_path' => $m->equipment->qr_code_image_path,
                'location' => $m->equipment->location,
                'last_maintenance_at' => $m->equipment->last_maintenance_at?->toDateString(),
                'next_maintenance_at' => $m->equipment->next_maintenance_at?->toDateString(),
                'image_path' => $m->equipment->image_path,
                'created_at' => $m->equipment->created_at?->toIso8601String(),
                'updated_at' => $m->equipment->updated_at?->toIso8601String(),
            ] : null,
            'performer' => null,
        ];
    }
}

