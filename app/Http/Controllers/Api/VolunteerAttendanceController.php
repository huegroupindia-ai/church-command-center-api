<?php

namespace App\Http\Controllers\Api;

use App\Events\VolunteerCheckedIn;
use App\Events\DashboardRefresh;
use App\Http\Controllers\Controller;
use App\Models\VolunteerAttendance;
use App\Models\User;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VolunteerAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = VolunteerAttendance::query()->with(['user', 'service']);

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->query('service_id'));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $attendance = $query->orderBy('created_at', 'desc')->get()->map(fn ($a) => $this->format($a));

        return response()->json(['data' => $attendance]);
    }

    public function store(Request $request)
    {
        $data = $this->validateAttendance($request);
        $attendance = VolunteerAttendance::create($data);
        $attendance->load(['user', 'service']);

        return response()->json($this->format($attendance), 201);
    }

    public function show($id)
    {
        $attendance = VolunteerAttendance::with(['user', 'service'])->findOrFail($id);

        return response()->json($this->format($attendance));
    }

    public function update(Request $request, $id)
    {
        $attendance = VolunteerAttendance::findOrFail($id);
        $data = $this->validateAttendance($request, false);
        $attendance->update($data);
        $attendance->load(['user', 'service']);

        return response()->json($this->format($attendance));
    }

    public function destroy($id)
    {
        VolunteerAttendance::findOrFail($id)->delete();

        return response()->json(['message' => 'Volunteer attendance deleted']);
    }

    public function checkIn(Request $request, $id)
    {
        $attendance = VolunteerAttendance::findOrFail($id);
        $attendance->update([
            'check_in_time' => now(),
            'status' => 'present',
        ]);
        $attendance->load(['user', 'service']);

        broadcast(new VolunteerCheckedIn([
            'volunteer_name' => $attendance->user?->name ?? '',
            'department' => '',
            'status' => 'present',
            'service_name' => $attendance->service?->name ?? '',
            'checked_in_at' => now()->toIso8601String(),
        ]));
        broadcast(new DashboardRefresh(reason: 'volunteer_checked_in'));

        return response()->json($this->format($attendance));
    }

    public function checkOut(Request $request, $id)
    {
        $attendance = VolunteerAttendance::findOrFail($id);
        $attendance->update([
            'check_out_time' => now(),
            'status' => 'completed',
        ]);
        $attendance->load(['user', 'service']);

        broadcast(new DashboardRefresh(reason: 'volunteer_checked_out'));

        return response()->json($this->format($attendance));
    }

    protected function validateAttendance(Request $request, bool $required = true): array
    {
        $rule = $required ? 'required' : 'sometimes';

        return $request->validate([
            'user_id' => [$rule, 'integer', 'exists:users,id'],
            'service_id' => [$rule, 'integer', 'exists:services,id'],
            'check_in_time' => ['nullable', 'date'],
            'check_out_time' => ['nullable', 'date'],
            'status' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    protected function format(VolunteerAttendance $a): array
    {
        return [
            'id' => $a->id,
            'user_id' => $a->user_id,
            'service_id' => $a->service_id,
            'check_in_time' => $a->check_in_time?->toIso8601String(),
            'check_out_time' => $a->check_out_time?->toIso8601String(),
            'status' => $a->status ?? 'absent',
            'notes' => $a->notes,
            'created_at' => $a->created_at?->toIso8601String(),
            'updated_at' => $a->updated_at?->toIso8601String(),
            'user' => $a->user ? [
                'id' => $a->user->id,
                'name' => $a->user->name,
                'email' => $a->user->email,
                'phone' => $a->user->phone,
                'avatar' => $a->user->avatar,
                'role' => $a->user->role,
                'is_active' => $a->user->is_active,
            ] : null,
            'service' => $a->service ? [
                'id' => $a->service->id,
                'name' => $a->service->name,
                'service_date' => $a->service->service_date instanceof \Carbon\Carbon
                    ? $a->service->service_date->format('Y-m-d')
                    : $a->service->service_date,
                'start_time' => (string) $a->service->start_time,
                'end_time' => (string) $a->service->end_time,
                'status' => $a->service->status ?? 'draft',
            ] : null,
        ];
    }
}
