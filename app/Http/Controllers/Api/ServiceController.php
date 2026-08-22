<?php

namespace App\Http\Controllers\Api;

use App\Events\ServiceReadinessUpdated;
use App\Events\DashboardRefresh;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::query()->orderBy('service_date', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('from_date')) {
            $query->whereDate('service_date', '>=', $request->query('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('service_date', '<=', $request->query('to_date'));
        }

        $services = $query->get()->map(fn ($s) => $this->format($s));

        return response()->json(['data' => $services]);
    }

    public function store(Request $request)
    {
        $data = $this->validateService($request);
        $data['created_by'] = Auth::guard('api')->id() ?? 1;
        $data['church_id'] = $data['church_id'] ?? 1;

        $service = Service::create($data);

        broadcast(new DashboardRefresh(reason: 'service_created'));

        return response()->json($this->format($service), 201);
    }

    public function show($id)
    {
        $service = Service::findOrFail($id);

        return response()->json($this->format($service));
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        $data = $this->validateService($request, false);
        $service->update($data);

        broadcast(new DashboardRefresh(reason: 'service_updated'));

        return response()->json($this->format($service));
    }

    public function destroy($id)
    {
        Service::findOrFail($id)->delete();

        return response()->json(['message' => 'Service deleted']);
    }

    public function updateStatus(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        $request->validate(['status' => ['required', 'string']]);
        $service->update(['status' => $request->input('status')]);

        broadcast(new ServiceReadinessUpdated([
            'service_id' => $service->id,
            'service_name' => $service->name,
            'readiness_score' => 0,
            'departments' => [],
        ]));
        broadcast(new DashboardRefresh(reason: 'service_status_changed'));

        return response()->json($this->format($service));
    }

    public function timeline($id)
    {
        Service::findOrFail($id);

        return response()->json(['data' => []]);
    }

    public function assignVolunteer(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        return response()->json($this->format($service));
    }

    protected function validateService(Request $request, bool $required = true): array
    {
        $rule = $required ? 'required' : 'sometimes';

        return $request->validate([
            'church_id' => ['nullable', 'integer'],
            'name' => [$rule, 'string', 'max:255'],
            'service_date' => [$rule, 'date'],
            'start_time' => [$rule, 'string'],
            'end_time' => [$rule, 'string'],
            'service_type' => ['nullable', 'string'],
            'speaker' => ['nullable', 'string'],
            'worship_leader' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);
    }

    protected function format(Service $s): array
    {
        return [
            'id' => $s->id,
            'church_id' => $s->church_id ?? 1,
            'name' => $s->name,
            'service_date' => $s->service_date instanceof \Carbon\Carbon
                ? $s->service_date->format('Y-m-d')
                : $s->service_date,
            'start_time' => substr((string) $s->start_time, 0, 5),
            'end_time' => substr((string) $s->end_time, 0, 5),
            'service_type' => $s->service_type ?? 'sunday_morning',
            'speaker' => $s->speaker,
            'worship_leader' => $s->worship_leader,
            'notes' => $s->notes,
            'status' => $s->status ?? 'draft',
            'created_by' => $s->created_by ?? 1,
            'created_at' => $s->created_at?->toIso8601String(),
            'updated_at' => $s->updated_at?->toIso8601String(),
            'sections' => [],
            'checklists' => [],
        ];
    }
}
