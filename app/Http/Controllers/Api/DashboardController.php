<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\Incident;
use App\Models\Service;
use App\Models\ServiceChecklist;
use App\Models\ServiceChecklistItem;
use App\Models\VolunteerAttendance;
use App\Models\VolunteerSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();
        $churchId = $user->church_id ?? 1;
        $today = Carbon::today();

        return response()->json([
            'todays_service' => $this->getTodaysService($churchId, $today),
            'quick_stats' => $this->getQuickStats($churchId),
            'department_readiness' => $this->getDepartmentReadiness($churchId),
            'equipment_alerts' => $this->getEquipmentAlerts($churchId, $today),
            'volunteer_attendance' => $this->getVolunteerAttendance($churchId, $today),
            'upcoming_events' => $this->getUpcomingEvents($churchId, $today),
            'recent_activity' => $this->getRecentActivity($churchId),
            'overall_readiness' => $this->getOverallReadiness($churchId),
        ]);
    }

    private function getTodaysService(int $churchId, Carbon $today): ?array
    {
        $service = Service::where('church_id', $churchId)
            ->whereDate('service_date', $today)
            ->first();

        if (!$service) {
            // Return the next upcoming service instead
            $service = Service::where('church_id', $churchId)
                ->whereDate('service_date', '>=', $today)
                ->orderBy('service_date', 'asc')
                ->first();
        }

        if (!$service) {
            return null;
        }

        return [
            'id' => $service->id,
            'name' => $service->name,
            'date' => $service->service_date->format('Y-m-d'),
            'time' => substr((string) $service->start_time, 0, 5) . ' – ' . substr((string) $service->end_time, 0, 5),
            'type' => $service->service_type,
            'speaker' => $service->speaker,
            'worship_leader' => $service->worship_leader,
            'status' => $service->status,
        ];
    }

    private function getQuickStats(int $churchId): array
    {
        $serviceIds = Service::where('church_id', $churchId)->pluck('id');
        $checklistIds = ServiceChecklist::whereIn('service_id', $serviceIds)->pluck('id');

        $items = ServiceChecklistItem::whereIn('checklist_id', $checklistIds);
        $totalTasks = (clone $items)->count();
        $completed = (clone $items)->whereIn('status', ['completed', 'verified', 'approved'])->count();
        $pending = (clone $items)->where('status', 'pending')->count();
        $verified = (clone $items)->where('status', 'verified')->count();

        $openIncidents = Incident::where('church_id', $churchId)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        $faultyEquipment = Equipment::where('church_id', $churchId)
            ->where('status', 'faulty')
            ->count();

        return [
            'total_tasks' => $totalTasks,
            'completed' => $completed,
            'pending' => $pending,
            'verified' => $verified,
            'open_incidents' => $openIncidents,
            'equipment_faults' => $faultyEquipment,
        ];
    }

    private function getDepartmentReadiness(int $churchId): array
    {
        $departments = Department::where('church_id', $churchId)
            ->where('is_active', true)
            ->get();

        return $departments->map(function ($dept) {
            $checklistIds = ServiceChecklist::where('department_id', $dept->id)->pluck('id');
            $items = ServiceChecklistItem::whereIn('checklist_id', $checklistIds);
            $total = (clone $items)->count();
            $done = (clone $items)->whereIn('status', ['completed', 'verified', 'approved'])->count();

            return [
                'id' => $dept->id,
                'name' => $dept->name,
                'score' => $total > 0 ? round(($done / $total) * 100) : 0,
                'total_tasks' => $total,
                'completed_tasks' => $done,
            ];
        })->toArray();
    }

    private function getEquipmentAlerts(int $churchId, Carbon $today): array
    {
        return Equipment::where('church_id', $churchId)
            ->where(function ($q) use ($today) {
                $q->whereIn('status', ['faulty', 'maintenance'])
                    ->orWhere(function ($q2) use ($today) {
                        $q2->whereNotNull('next_maintenance_at')
                            ->where('next_maintenance_at', '<=', $today);
                    });
            })
            ->limit(10)
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'status' => $e->status,
                'next_maintenance' => $e->next_maintenance_at?->toDateString(),
            ])
            ->toArray();
    }

    private function getVolunteerAttendance(int $churchId, Carbon $today): array
    {
        $todayService = Service::where('church_id', $churchId)
            ->whereDate('service_date', $today)
            ->first();

        if (!$todayService) {
            return ['present' => 0, 'late' => 0, 'absent' => 0, 'expected' => 0];
        }

        $scheduled = VolunteerSchedule::where('service_id', $todayService->id)->count();
        $attendance = VolunteerAttendance::where('service_id', $todayService->id)->get();

        $present = $attendance->whereIn('status', ['present', 'completed'])->count();
        $late = $attendance->where('status', 'late')->count();
        $absent = max($scheduled - $attendance->count(), 0);

        return [
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'expected' => $scheduled,
        ];
    }

    private function getUpcomingEvents(int $churchId, Carbon $today): array
    {
        return Service::where('church_id', $churchId)
            ->whereDate('service_date', '>=', $today)
            ->orderBy('service_date', 'asc')
            ->limit(5)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'date' => $s->service_date->format('M d, Y'),
                'start_time' => substr((string) $s->start_time, 0, 5),
                'type' => $s->service_type,
                'speaker' => $s->speaker,
                'status' => $s->status,
            ])
            ->toArray();
    }

    private function getRecentActivity(int $churchId): array
    {
        $activities = [];

        // Recent checklist completions
        if (Schema::hasTable('service_checklist_items')) {
            $recentItems = ServiceChecklistItem::whereNotNull('completed_at')
                ->whereHas('checklist', function ($q) use ($churchId) {
                    $q->whereHas('service', fn($sq) => $sq->where('church_id', $churchId));
                })
                ->with('checklist.service')
                ->orderBy('completed_at', 'desc')
                ->limit(5)
                ->get();

            foreach ($recentItems as $item) {
                $activities[] = [
                    'type' => 'checklist_completed',
                    'message' => "Checklist item \"{$item->title}\" was completed",
                    'timestamp' => $item->completed_at->toIso8601String(),
                    'related_service' => $item->checklist?->service?->name,
                ];
            }
        }

        // Recent incidents
        $recentIncidents = Incident::where('church_id', $churchId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($recentIncidents as $incident) {
            $activities[] = [
                'type' => 'incident_' . $incident->status,
                'message' => "Incident \"{$incident->title}\" ({$incident->status})",
                'timestamp' => $incident->created_at->toIso8601String(),
            ];
        }

        // Sort by timestamp descending
        usort($activities, fn($a, $b) => strcmp($b['timestamp'], $a['timestamp']));

        return array_slice($activities, 0, 10);
    }

    private function getOverallReadiness(int $churchId): int
    {
        $serviceIds = Service::where('church_id', $churchId)->pluck('id');
        $checklistIds = ServiceChecklist::whereIn('service_id', $serviceIds)->pluck('id');

        $total = ServiceChecklistItem::whereIn('checklist_id', $checklistIds)->count();
        $completed = ServiceChecklistItem::whereIn('checklist_id', $checklistIds)
            ->whereIn('status', ['completed', 'verified', 'approved'])
            ->count();

        return $total > 0 ? round(($completed / $total) * 100) : 0;
    }
}
