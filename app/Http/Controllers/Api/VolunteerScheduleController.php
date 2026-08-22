<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VolunteerSchedule;
use App\Models\Department;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VolunteerScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = VolunteerSchedule::query()->with(['user', 'department', 'service', 'attendance']);

        if ($request->filled('from_date')) {
            $query->whereDate('scheduled_date', '>=', $request->query('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('scheduled_date', '<=', $request->query('to_date'));
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->query('department_id'));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        $schedules = $query->orderBy('scheduled_date', 'desc')->get()->map(fn ($s) => $this->format($s));

        return response()->json(['data' => $schedules]);
    }

    public function store(Request $request)
    {
        $data = $this->validateSchedule($request);
        $schedule = VolunteerSchedule::create($data);
        $schedule->load(['user', 'department', 'service', 'attendance']);

        return response()->json($this->format($schedule), 201);
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'schedules' => ['required', 'array'],
            'schedules.*' => ['required', 'array'],
        ]);

        $created = [];
        foreach ($request->input('schedules', []) as $item) {
            $validated = validator($item, [
                'user_id' => ['required', 'integer', 'exists:users,id'],
                'department_id' => ['nullable', 'integer', 'exists:departments,id'],
                'service_id' => ['nullable', 'integer', 'exists:services,id'],
                'scheduled_date' => ['required', 'date'],
                'start_time' => ['required', 'string'],
                'end_time' => ['required', 'string'],
                'status' => ['nullable', 'string'],
                'notes' => ['nullable', 'string'],
            ])->validate();

            $schedule = VolunteerSchedule::create($validated);
            $schedule->load(['user', 'department', 'service', 'attendance']);
            $created[] = $this->format($schedule);
        }

        return response()->json(['data' => $created], 201);
    }

    public function show($id)
    {
        $schedule = VolunteerSchedule::with(['user', 'department', 'service', 'attendance'])->findOrFail($id);

        return response()->json($this->format($schedule));
    }

    public function update(Request $request, $id)
    {
        $schedule = VolunteerSchedule::findOrFail($id);
        $data = $this->validateSchedule($request, false);
        $schedule->update($data);
        $schedule->load(['user', 'department', 'service', 'attendance']);

        return response()->json($this->format($schedule));
    }

    public function destroy($id)
    {
        VolunteerSchedule::findOrFail($id)->delete();

        return response()->json(['message' => 'Volunteer schedule deleted']);
    }

    protected function validateSchedule(Request $request, bool $required = true): array
    {
        $rule = $required ? 'required' : 'sometimes';

        return $request->validate([
            'user_id' => [$rule, 'integer', 'exists:users,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'scheduled_date' => [$rule, 'date'],
            'start_time' => [$rule, 'string'],
            'end_time' => [$rule, 'string'],
            'status' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    protected function format(VolunteerSchedule $s): array
    {
        return [
            'id' => $s->id,
            'user_id' => $s->user_id,
            'department_id' => $s->department_id ?? 1,
            'service_id' => $s->service_id,
            'scheduled_date' => $s->scheduled_date instanceof \Carbon\Carbon
                ? $s->scheduled_date->format('Y-m-d')
                : $s->scheduled_date,
            'start_time' => (string) $s->start_time,
            'end_time' => (string) $s->end_time,
            'status' => $s->status ?? 'scheduled',
            'notes' => $s->notes,
            'created_at' => $s->created_at?->toIso8601String(),
            'updated_at' => $s->updated_at?->toIso8601String(),
            'user' => $s->user ? [
                'id' => $s->user->id,
                'name' => $s->user->name,
                'email' => $s->user->email,
                'phone' => $s->user->phone,
                'avatar' => $s->user->avatar,
                'role' => $s->user->role,
                'is_active' => $s->user->is_active,
            ] : null,
            'department' => $s->department ? [
                'id' => $s->department->id,
                'name' => $s->department->name,
                'description' => $s->department->description,
                'head' => $s->department->head,
                'is_active' => $s->department->is_active,
            ] : null,
            'service' => $s->service ? [
                'id' => $s->service->id,
                'name' => $s->service->name,
                'service_date' => $s->service->service_date instanceof \Carbon\Carbon
                    ? $s->service->service_date->format('Y-m-d')
                    : $s->service->service_date,
                'start_time' => (string) $s->service->start_time,
                'end_time' => (string) $s->service->end_time,
                'status' => $s->service->status ?? 'draft',
            ] : null,
            'attendance' => $s->attendance->map(fn ($a) => [
                'id' => $a->id,
                'user_id' => $a->user_id,
                'service_id' => $a->service_id,
                'check_in_time' => $a->check_in_time?->toIso8601String(),
                'check_out_time' => $a->check_out_time?->toIso8601String(),
                'status' => $a->status ?? 'absent',
                'notes' => $a->notes,
            ])->all(),
        ];
    }
}
