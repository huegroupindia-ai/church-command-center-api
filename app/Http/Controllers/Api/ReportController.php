<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceChecklist;
use App\Models\ServiceChecklistItem;
use App\Models\VolunteerAttendance;
use App\Models\VolunteerSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    public function serviceReadiness($serviceId)
    {
        $service = Service::find($serviceId);

        $items = ServiceChecklistItem::whereIn(
            'checklist_id',
            ServiceChecklist::where('service_id', $serviceId)->pluck('id')
        );

        $total = (clone $items)->count();
        $completed = (clone $items)->whereIn('status', ['completed', 'verified', 'approved'])->count();
        $pending = max($total - $completed, 0);
        $score = $total > 0 ? round(($completed / $total) * 100) : 0;

        return response()->json([
            'service_id' => (int) $serviceId,
            'service_name' => $service->name ?? 'Service',
            'overall_score' => $score,
            'score' => $score,
            'total_tasks' => $total,
            'completed_tasks' => $completed,
            'pending_tasks' => $pending,
            'departments' => [],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function volunteerPerformance(Request $request)
    {
        $attQuery = VolunteerAttendance::query();
        if ($request->filled('from_date')) {
            $attQuery->whereDate('check_in_time', '>=', $request->query('from_date'));
        }
        if ($request->filled('to_date')) {
            $attQuery->whereDate('check_in_time', '<=', $request->query('to_date'));
        }

        $totalVolunteers = VolunteerSchedule::distinct('user_id')->count('user_id');
        $present = (clone $attQuery)->whereIn('status', ['present', 'completed'])->count();
        $totalRecords = (clone $attQuery)->count();
        $attendanceRate = $totalRecords > 0 ? round(($present / $totalRecords) * 100) : 0;

        return response()->json([
            'total_volunteers' => $totalVolunteers,
            'total_attendance' => $present,
            'avg_punctuality' => $attendanceRate,
            'completion_rate' => $attendanceRate,
            'volunteers' => [],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function equipmentHealth()
    {
        $total = $this->safeCount('equipment');
        $active = $this->safeCount('equipment', ['status' => 'active']);
        $faults = $this->safeCount('equipment_fault_reports', ['status' => 'open']);

        return response()->json([
            'total_equipment' => $total,
            'active_equipment' => $active,
            'open_faults' => $faults,
            'health_score' => $total > 0 ? round(($active / $total) * 100) : 100,
            'equipment' => [],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function incidents(Request $request)
    {
        if (! Schema::hasTable('incidents')) {
            return response()->json(['total' => 0, 'incidents' => [], 'by_type' => [], 'by_severity' => []]);
        }

        $query = DB::table('incidents');
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->query('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->query('to_date'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->query('severity'));
        }

        $rows = $query->get();

        return response()->json([
            'total' => $rows->count(),
            'by_type' => $rows->groupBy('type')->map->count(),
            'by_severity' => $rows->groupBy('severity')->map->count(),
            'by_status' => $rows->groupBy('status')->map->count(),
            'incidents' => [],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function verification(Request $request)
    {
        $items = ServiceChecklistItem::query();
        if ($request->filled('from_date')) {
            $items->whereDate('created_at', '>=', $request->query('from_date'));
        }
        if ($request->filled('to_date')) {
            $items->whereDate('created_at', '<=', $request->query('to_date'));
        }

        $total = (clone $items)->count();
        $verified = (clone $items)->whereIn('status', ['verified', 'approved'])->count();

        return response()->json([
            'total_items' => $total,
            'verified_items' => $verified,
            'verification_rate' => $total > 0 ? round(($verified / $total) * 100) : 0,
            'departments' => [],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function export(Request $request)
    {
        return response()->json([
            'message' => 'Export queued',
            'type' => $request->query('type'),
            'format' => $request->query('format', 'pdf'),
            'url' => null,
        ]);
    }

    protected function safeCount(string $table, array $where = []): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }
        $q = DB::table($table);
        foreach ($where as $col => $val) {
            if (Schema::hasColumn($table, $col)) {
                $q->where($col, $val);
            }
        }

        return $q->count();
    }
}
