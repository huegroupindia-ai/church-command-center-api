<?php

namespace App\Http\Controllers\Api;

use App\Events\EquipmentAlert;
use App\Events\DashboardRefresh;
use App\Http\Controllers\Controller;
use App\Models\EquipmentFaultReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EquipmentFaultController extends Controller
{
    public function index(Request $request)
    {
        $query = EquipmentFaultReport::query()->orderBy('created_at', 'desc');

        if ($request->filled('equipment_id')) {
            $query->where('equipment_id', $request->query('equipment_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json([
            'data' => $query->get()->map(fn ($f) => $this->format($f)),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateFault($request);
        $data['reported_by'] = $data['reported_by'] ?? (Auth::guard('api')->id() ?? 1);

        $fault = EquipmentFaultReport::create($data);
        $fault->load('equipment');

        broadcast(new EquipmentAlert([
            'equipment_id' => $fault->equipment_id,
            'equipment_name' => $fault->equipment?->name ?? 'Unknown Equipment',
            'alert_type' => 'fault_report',
            'severity' => $fault->severity ?? 'medium',
            'message' => $fault->title,
            'reported_by' => Auth::guard('api')->user()?->name ?? '',
        ]));
        broadcast(new DashboardRefresh(reason: 'equipment_fault_reported'));

        return response()->json($this->format($fault), 201);
    }

    public function show($id)
    {
        $fault = EquipmentFaultReport::findOrFail($id);

        return response()->json($this->format($fault));
    }

    public function update(Request $request, $id)
    {
        $fault = EquipmentFaultReport::findOrFail($id);
        $data = $this->validateFault($request, false);
        $fault->update($data);
        $fault->load('equipment');

        broadcast(new DashboardRefresh(reason: 'equipment_fault_updated'));

        return response()->json($this->format($fault));
    }

    public function destroy($id)
    {
        EquipmentFaultReport::findOrFail($id)->delete();

        return response()->json(['message' => 'Fault report deleted']);
    }

    public function updateStatus(Request $request, $id)
    {
        $fault = EquipmentFaultReport::findOrFail($id);
        $request->validate(['status' => ['required', 'string']]);
        $data = ['status' => $request->input('status')];

        if ($request->input('status') === 'resolved') {
            $data['resolved_by'] = Auth::guard('api')->id() ?? 1;
            $data['resolved_at'] = $data['resolved_at'] ?? now()->toDateString();
        }

        $fault->update($data);

        return response()->json($this->format($fault));
    }

    protected function validateFault(Request $request, bool $required = true): array
    {
        $rule = $required ? 'required' : 'sometimes';

        return $request->validate([
            'equipment_id' => [$rule, 'integer'],
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

    protected function format(EquipmentFaultReport $f): array
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
            'equipment' => $f->equipment ? [
                'id' => $f->equipment->id,
                'church_id' => $f->equipment->church_id ?? 1,
                'category_id' => $f->equipment->category_id,
                'department_id' => $f->equipment->department_id,
                'name' => $f->equipment->name,
                'asset_id' => $f->equipment->asset_id,
                'description' => $f->equipment->description,
                'brand' => $f->equipment->brand,
                'model' => $f->equipment->model,
                'serial_number' => $f->equipment->serial_number,
                'purchase_date' => $f->equipment->purchase_date?->toDateString(),
                'warranty_expires_at' => $f->equipment->warranty_expires_at?->toDateString(),
                'purchase_price' => $f->equipment->purchase_price,
                'status' => $f->equipment->status ?? 'active',
                'qr_code' => $f->equipment->qr_code,
                'qr_code_image_path' => $f->equipment->qr_code_image_path,
                'location' => $f->equipment->location,
                'last_maintenance_at' => $f->equipment->last_maintenance_at?->toDateString(),
                'next_maintenance_at' => $f->equipment->next_maintenance_at?->toDateString(),
                'image_path' => $f->equipment->image_path,
                'created_at' => $f->equipment->created_at?->toIso8601String(),
                'updated_at' => $f->equipment->updated_at?->toIso8601String(),
            ] : null,
            'reporter' => null,
            'resolver' => null,
        ];
    }
}

