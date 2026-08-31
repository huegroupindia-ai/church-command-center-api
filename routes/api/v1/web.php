<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\IncidentMediaController;
use App\Http\Controllers\Api\VolunteerScheduleController;
use App\Http\Controllers\Api\VolunteerAttendanceController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\EquipmentCategoryController;
use App\Http\Controllers\Api\EquipmentMaintenanceController;
use App\Http\Controllers\Api\EquipmentFaultController;
use App\Http\Controllers\Api\ChecklistTemplateController;
use App\Http\Controllers\Api\ServiceChecklistController;
use App\Http\Controllers\Api\ServiceChecklistItemController;
use App\Http\Controllers\Api\EvidenceController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\DepartmentController;
use App\Events\DashboardRefresh;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::middleware('auth:api')->group(function () {
    // Broadcast authentication for private channels
    Route::post('/broadcasting/auth', function () {
        $user = \Illuminate\Support\Facades\Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        return response()->json(['id' => $user->id, 'name' => $user->name]);
    });

    // Test broadcast endpoint
    Route::post('/broadcast-test', function () {
        broadcast(new DashboardRefresh(reason: 'test_broadcast'));
        return response()->json(['message' => 'Broadcast sent']);
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [UserController::class, 'update']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/readiness', [DashboardController::class, 'index']);
    Route::get('/dashboard/quick-stats', [DashboardController::class, 'index']);
    Route::get('/dashboard/upcoming-events', [DashboardController::class, 'index']);
    Route::get('/dashboard/activity', [DashboardController::class, 'index']);

    Route::get('/users', [UserController::class, 'index']);
    Route::put('/users/{id}', [UserController::class, 'update']);

    Route::get('/services', [ServiceController::class, 'index']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
    Route::patch('/services/{id}/status', [ServiceController::class, 'updateStatus']);
    Route::get('/services/{id}/timeline', [ServiceController::class, 'timeline']);
    Route::post('/services/{id}/assign-volunteer', [ServiceController::class, 'assignVolunteer']);

    Route::get('/volunteer-schedules', [VolunteerScheduleController::class, 'index']);
    Route::post('/volunteer-schedules', [VolunteerScheduleController::class, 'store']);
    Route::post('/volunteer-schedules/bulk', [VolunteerScheduleController::class, 'bulkStore']);
    Route::get('/volunteer-schedules/{id}', [VolunteerScheduleController::class, 'show']);
    Route::put('/volunteer-schedules/{id}', [VolunteerScheduleController::class, 'update']);
    Route::delete('/volunteer-schedules/{id}', [VolunteerScheduleController::class, 'destroy']);

    Route::get('/volunteer-attendance', [VolunteerAttendanceController::class, 'index']);
    Route::post('/volunteer-attendance', [VolunteerAttendanceController::class, 'store']);
    Route::get('/volunteer-attendance/{id}', [VolunteerAttendanceController::class, 'show']);
    Route::put('/volunteer-attendance/{id}', [VolunteerAttendanceController::class, 'update']);
    Route::delete('/volunteer-attendance/{id}', [VolunteerAttendanceController::class, 'destroy']);
    Route::post('/volunteer-attendance/{id}/check-in', [VolunteerAttendanceController::class, 'checkIn']);
    Route::post('/volunteer-attendance/{id}/check-out', [VolunteerAttendanceController::class, 'checkOut']);

    Route::get('/checklist-templates', [ChecklistTemplateController::class, 'index']);
    Route::post('/checklist-templates', [ChecklistTemplateController::class, 'store']);
    Route::get('/checklist-templates/{id}', [ChecklistTemplateController::class, 'show']);
    Route::put('/checklist-templates/{id}', [ChecklistTemplateController::class, 'update']);
    Route::delete('/checklist-templates/{id}', [ChecklistTemplateController::class, 'destroy']);

    Route::get('/service-checklists', [ServiceChecklistController::class, 'index']);
    Route::post('/service-checklists', [ServiceChecklistController::class, 'store']);
    Route::get('/service-checklists/{id}', [ServiceChecklistController::class, 'show']);
    Route::put('/service-checklists/{id}', [ServiceChecklistController::class, 'update']);
    Route::delete('/service-checklists/{id}', [ServiceChecklistController::class, 'destroy']);

    Route::get('/service-checklist-items', [ServiceChecklistItemController::class, 'index']);
    Route::post('/service-checklist-items', [ServiceChecklistItemController::class, 'store']);
    Route::get('/service-checklist-items/{id}', [ServiceChecklistItemController::class, 'show']);
    Route::put('/service-checklist-items/{id}', [ServiceChecklistItemController::class, 'update']);
    Route::delete('/service-checklist-items/{id}', [ServiceChecklistItemController::class, 'destroy']);
    Route::post('/service-checklist-items/{id}/complete', [ServiceChecklistItemController::class, 'complete']);
    Route::post('/service-checklist-items/{id}/verify', [ServiceChecklistItemController::class, 'verify']);
    Route::post('/service-checklist-items/{id}/approve', [ServiceChecklistItemController::class, 'approve']);
    Route::post('/service-checklist-items/{id}/reject', [ServiceChecklistItemController::class, 'reject']);

    Route::get('/evidence', [EvidenceController::class, 'index']);
    Route::post('/evidence', [EvidenceController::class, 'store']);
    Route::delete('/evidence/{id}', [EvidenceController::class, 'destroy']);

    Route::get('/reports/service-readiness/{serviceId}', [ReportController::class, 'serviceReadiness']);
    Route::get('/reports/volunteer-performance', [ReportController::class, 'volunteerPerformance']);
    Route::get('/reports/equipment-health', [ReportController::class, 'equipmentHealth']);
    Route::get('/reports/incidents', [ReportController::class, 'incidents']);
    Route::get('/reports/verification', [ReportController::class, 'verification']);
    Route::get('/reports/export', [ReportController::class, 'export']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/{id}', [NotificationController::class, 'show'])->whereNumber('id');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->whereNumber('id');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->whereNumber('id');
    Route::post('/notifications/send', [NotificationController::class, 'send']);
    Route::post('/notifications/send-department', [NotificationController::class, 'sendDepartment']);
    Route::post('/notifications/service-reminder/{serviceId}', [NotificationController::class, 'serviceReminder']);
    Route::post('/notifications/remove-token', [NotificationController::class, 'removeToken']);

    // Donations / Giving
    Route::get('/donations', [DonationController::class, 'index']);
    Route::post('/donations', [DonationController::class, 'store']);
    Route::get('/donations/summary', [DonationController::class, 'summary']);
    Route::get('/donations/{id}', [DonationController::class, 'show']);
    Route::put('/donations/{id}', [DonationController::class, 'update']);
    Route::delete('/donations/{id}', [DonationController::class, 'destroy']);

    // Department Management
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::post('/departments', [DepartmentController::class, 'store']);
    Route::get('/departments/{id}', [DepartmentController::class, 'show']);
    Route::put('/departments/{id}', [DepartmentController::class, 'update']);
    Route::delete('/departments/{id}', [DepartmentController::class, 'destroy']);


    // Announcements
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::post('/announcements', [AnnouncementController::class, 'store']);
    Route::get('/announcements/{id}', [AnnouncementController::class, 'show']);
    Route::put('/announcements/{id}', [AnnouncementController::class, 'update']);
    Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy']);

    // Chat / Messaging
    Route::get('/conversations', [ChatController::class, 'index']);
    Route::post('/conversations', [ChatController::class, 'store']);
    Route::get('/conversations/{id}', [ChatController::class, 'show'])->whereNumber('id');
    Route::delete('/conversations/{id}', [ChatController::class, 'destroy'])->whereNumber('id');
    Route::post('/conversations/{id}/messages', [ChatController::class, 'sendMessage'])->whereNumber('id');
    Route::get('/conversations/{id}/messages', [ChatController::class, 'messages'])->whereNumber('id');
    Route::post('/conversations/{id}/read', [ChatController::class, 'markRead'])->whereNumber('id');


    $resources = [
        'departments',
    ];

    Route::get('/incidents', [IncidentController::class, 'index']);
    Route::post('/incidents', [IncidentController::class, 'store']);
    Route::get('/incidents/{id}', [IncidentController::class, 'show']);
    Route::put('/incidents/{id}', [IncidentController::class, 'update']);
    Route::delete('/incidents/{id}', [IncidentController::class, 'destroy']);
    Route::patch('/incidents/{id}/status', [IncidentController::class, 'updateStatus']);
    Route::patch('/incidents/{id}/assign', [IncidentController::class, 'assign']);
    Route::get('/incidents/{id}/timeline', [IncidentController::class, 'timeline']);
    Route::post('/incidents/{id}/media', [IncidentController::class, 'addMedia']);
    Route::delete('/incidents/{id}/media/{mediaId}', [IncidentController::class, 'deleteMedia']);

    Route::get('/incident-media', [IncidentMediaController::class, 'index']);
    Route::post('/incident-media', [IncidentMediaController::class, 'store']);
    Route::get('/incident-media/{id}', [IncidentMediaController::class, 'show']);
    Route::put('/incident-media/{id}', [IncidentMediaController::class, 'update']);
    Route::delete('/incident-media/{id}', [IncidentMediaController::class, 'destroy']);

    foreach ($resources as $resource) {
        Route::get("/{$resource}", [ResourceController::class, 'index']);
        Route::post("/{$resource}", [ResourceController::class, 'store']);
        Route::get("/{$resource}/{id}", [ResourceController::class, 'show']);
        Route::put("/{$resource}/{id}", [ResourceController::class, 'update']);
        Route::delete("/{$resource}/{id}", [ResourceController::class, 'destroy']);
    }

    Route::get('/equipment', [EquipmentController::class, 'index']);
    Route::post('/equipment', [EquipmentController::class, 'store']);
    Route::get('/equipment/{id}', [EquipmentController::class, 'show']);
    Route::put('/equipment/{id}', [EquipmentController::class, 'update']);
    Route::delete('/equipment/{id}', [EquipmentController::class, 'destroy']);
    Route::patch('/equipment/{id}/status', [EquipmentController::class, 'updateStatus']);
    Route::get('/equipment/{id}/maintenance', [EquipmentController::class, 'maintenanceLogs']);
    Route::post('/equipment/{id}/maintenance', [EquipmentController::class, 'logMaintenance']);
    Route::get('/equipment/{id}/faults', [EquipmentController::class, 'faultReports']);
    Route::post('/equipment/{id}/faults', [EquipmentController::class, 'reportFault']);

    Route::get('/equipment-categories', [EquipmentCategoryController::class, 'index']);
    Route::post('/equipment-categories', [EquipmentCategoryController::class, 'store']);
    Route::get('/equipment-categories/{id}', [EquipmentCategoryController::class, 'show']);
    Route::put('/equipment-categories/{id}', [EquipmentCategoryController::class, 'update']);
    Route::delete('/equipment-categories/{id}', [EquipmentCategoryController::class, 'destroy']);

    Route::get('/equipment-maintenance', [EquipmentMaintenanceController::class, 'index']);
    Route::post('/equipment-maintenance', [EquipmentMaintenanceController::class, 'store']);
    Route::get('/equipment-maintenance/{id}', [EquipmentMaintenanceController::class, 'show']);
    Route::put('/equipment-maintenance/{id}', [EquipmentMaintenanceController::class, 'update']);
    Route::delete('/equipment-maintenance/{id}', [EquipmentMaintenanceController::class, 'destroy']);

    Route::get('/equipment-faults', [EquipmentFaultController::class, 'index']);
    Route::post('/equipment-faults', [EquipmentFaultController::class, 'store']);
    Route::get('/equipment-faults/{id}', [EquipmentFaultController::class, 'show']);
    Route::put('/equipment-faults/{id}', [EquipmentFaultController::class, 'update']);
    Route::delete('/equipment-faults/{id}', [EquipmentFaultController::class, 'destroy']);
    Route::patch('/equipment-faults/{id}/status', [EquipmentFaultController::class, 'updateStatus']);
});
