<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\EquipmentFaultReport;
use App\Models\EquipmentMaintenanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Equipment::query()->orderBy('name', 'asc');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->query('department_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json([
            'data' => $query->get()->map(fn ($e) => $this->format($e)),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateEquipment($request);
        $data['created_by'] = Auth::guard('api')->id() ?? 1;
        $data['church_id'] = $data['church_id'] ?? 1;
        $data['qr_code'] = $data['qr_code'] ?? ('EQ-' . uniqid());
        $data['asset_id'] = $data['asset_id'] ?? ('AST-' . strtoupper(substr(uniqid(), -8)));

        $equipment = Equipment::create($data);

        return response()->json($this->format($equipment), 201);
    }

    public function show($id)
    {
        $equipment = Equipment::findOrFail($id);

        return response()->json($this->format($equipment));
    }

    public function update(Request $request, $id)
    {
        $equipment = Equipment::findOrFail($id);
        $data = $this->validateEquipment($request, false);
        $equipment->update($data);

        return response()->json($this->format($equipment));
    }

    public function destroy($id)
    {
        Equipment::findOrFail($id)->delete();

        return response()->json(['message' => 'Equipment deleted']);
    }

    public function updateStatus(Request $request, $id)
    {
        $equipment = Equipment::findOrFail($id);
        $request->validate(['status' => ['required', 'string']]);
        $equipment->update(['status' => $request->input('status')]);

        return response()->json($this->format($equipment));
    }

    public function maintenanceLogs($id)
    {
        $equipment = Equipment::findOrFail($id);

        return response()->json([
            'data' => $equipment->maintenanceLogs->map(fn ($m) => $this->formatMaintenance($m)),
        ]);
    }

    public function logMaintenance(Request $request, $id)
    {
        $equipment = Equipment::findOrFail($id);
        $data = $this->validateMaintenance($request);
        $data['equipment_id'] = $equipment->id;
        $data['performed_by'] = $data['performed_by'] ?? (Auth::guard('api')->id() ?? 1);

        $log = EquipmentMaintenanceLog::create($data);

        $equipment->update([
            'last_maintenance_at' => $data['performed_at'],
            'next_maintenance_at' => $data['next_maintenance_at'] ?? $equipment->next_maintenance_at,
        ]);

        return response()->json($this->format($equipment), 201);
    }

    public function faultReports($id)
    {
        $equipment = Equipment::findOrFail($id);

        return response()->json([
            'data' => $equipment->faultReports->map(fn ($f) => $this->formatFault($f)),
        ]);
    }

    public function reportFault(Request $request, $id)
    {
        $equipment = Equipment::findOrFail($id);
        $data = $this->validateFault($request);
        $data['equipment_id'] = $equipment->id;
        $data['reported_by'] = $data['reported_by'] ?? (Auth::guard('api')->id() ?? 1);

        $fault = EquipmentFaultReport::create($data);

        return response()->json($this->format($equipment), 201);
    }

    protected function validateEquipment(Request $request, bool $required = true): array
    {
        $rule = $required ? 'required' : 'sometimes';

        return $request->validate([
            'church_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'name' => [$rule, 'string', 'max:255'],
            'asset_id' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'warranty_expires_at' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string', 'max:255'],
            'qr_code' => ['nullable', 'string', 'max:255'],
            'qr_code_image_path' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'last_maintenance_at' => ['nullable', 'date'],
            'next_maintenance_at' => ['nullable', 'date'],
            'image_path' => ['nullable', 'string', 'max:255'],
        ]);
    }

    protected function validateMaintenance(Request $request, bool $required = true): array
    {
        $rule = $required ? 'required' : 'sometimes';

        return $request->validate([
            'performed_by' => ['nullable', 'integer'],
            'type' => [$rule, 'string', 'max:255'],
            'description' => [$rule, 'string'],
            'cost' => ['nullable', 'numeric'],
            'performed_at' => [$rule, 'date'],
            'next_maintenance_at' => ['nullable', 'date'],
        ]);
    }

    protected function validateFault(Request $request, bool $required = true): array
    {
        $rule = $required ? 'required' : 'sometimes';

        return $request->validate([
            'reported_by' => ['nullable', 'integer'],
            'title' => [$rule, 'string', 'max:255'],
            'description' => [$rule, 'string'],
            'severity' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'resolved_by' => ['nullable', 'integer'],
            'resolved_at' => ['nullable', 'date'],
            'resolution_notes' => ['nullable', 'string'],
        ]);
    }

    protected function format(Equipment $e): array
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
            'category' => $e->category ? [
                'id' => $e->category->id,
                'church_id' => $e->category->church_id ?? 1,
                'name' => $e->category->name,
                'description' => $e->category->description,
                'icon' => $e->category->icon,
                'created_at' => $e->category->created_at?->toIso8601String(),
                'updated_at' => $e->category->updated_at?->toIso8601String(),
            ] : null,
            'department' => null,
        ];
    }

    protected function formatMaintenance(EquipmentMaintenanceLog $m): array
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
            'equipment' => $m->equipment ? $this->format($m->equipment) : null,
            'performer' => null,
        ];
    }

    protected function formatFault(EquipmentFaultReport $f): array
    {
        return [
            'id' => $f->id,
            'equipment_id' => $f->equipment_id,
            'reported_by' => $f->reported_by,
            'title' => $f->title,
            'description' => $f->description,
            'severity' => $f->severity ?? 'medium',
            'status' => $f->status ?? 'open',
            'resolved_by' => $f->resolved_by,
            'resolved_at' => $f->resolved_at?->toDateString(),
            'resolution_notes' => $f->resolution_notes,
            'created_at' => $f->created_at?->toIso8601String(),
            'updated_at' => $f->updated_at?->toIso8601String(),
            'equipment' => $f->equipment ? $this->format($f->equipment) : null,
            'reporter' => null,
            'resolver' => null,
        ];
    }
}

