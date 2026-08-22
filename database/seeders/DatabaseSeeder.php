<?php

namespace Database\Seeders;

use App\Models\Church;
use App\Models\Department;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateItem;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentFaultReport;
use App\Models\EquipmentMaintenanceLog;
use App\Models\Incident;
use App\Models\Service;
use App\Models\ServiceChecklist;
use App\Models\ServiceChecklistItem;
use App\Models\User;
use App\Models\VolunteerAttendance;
use App\Models\VolunteerSchedule;
use App\Models\AppNotification;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ── 1. Church ──
        $church = Church::create([
            'name' => 'Grace Community Church',
            'slug' => 'grace-community',
            'address' => '1234 Worship Way',
            'city' => 'Nashville',
            'state' => 'TN',
            'country' => 'US',
            'phone' => '(615) 555-0123',
            'email' => 'info@gracecommunity.church',
            'timezone' => 'America/Chicago',
            'subscription_plan' => 'premium',
            'is_active' => true,
        ]);

        // ── 2. Users ──
        $password = Hash::make('password');

        $admin = User::create([
            'name' => 'Pastor James Mitchell',
            'email' => 'pastor@gracecommunity.church',
            'password' => $password,
            'phone' => '(615) 555-1001',
            'role' => 'admin',
            'church_id' => $church->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $sarah = User::create([
            'name' => 'Sarah Chen',
            'email' => 'sarah@gracecommunity.church',
            'password' => $password,
            'phone' => '(615) 555-1002',
            'role' => 'admin',
            'church_id' => $church->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Department Leaders
        $david = User::create([
            'name' => 'David Kim',
            'email' => 'david@gracecommunity.church',
            'password' => $password,
            'phone' => '(615) 555-2001',
            'role' => 'dept_leader',
            'church_id' => $church->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $maria = User::create([
            'name' => 'Maria Santos',
            'email' => 'maria@gracecommunity.church',
            'password' => $password,
            'phone' => '(615) 555-2002',
            'role' => 'dept_leader',
            'church_id' => $church->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $tom = User::create([
            'name' => 'Tom Wilson',
            'email' => 'tom@gracecommunity.church',
            'password' => $password,
            'phone' => '(615) 555-2003',
            'role' => 'dept_leader',
            'church_id' => $church->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Volunteers
        $volunteers = [];
        $volunteerData = [
            ['Alex Johnson', 'alex@gracecommunity.church', '(615) 555-3001'],
            ['Emily Brown', 'emily@gracecommunity.church', '(615) 555-3002'],
            ['Michael Lee', 'michael@gracecommunity.church', '(615) 555-3003'],
            ['Rachel Davis', 'rachel@gracecommunity.church', '(615) 555-3004'],
            ['Chris Martinez', 'chris@gracecommunity.church', '(615) 555-3005'],
            ['Laura Taylor', 'laura@gracecommunity.church', '(615) 555-3006'],
            ['James Anderson', 'james.a@gracecommunity.church', '(615) 555-3007'],
            ['Priya Patel', 'priya@gracecommunity.church', '(615) 555-3008'],
        ];

        foreach ($volunteerData as $v) {
            $volunteers[] = User::create([
                'name' => $v[0],
                'email' => $v[1],
                'password' => $password,
                'phone' => $v[2],
                'role' => 'volunteer',
                'church_id' => $church->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        // Test user
        $testUser = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => $password,
            'phone' => '(615) 555-9999',
            'role' => 'admin',
            'church_id' => $church->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // ── 3. Departments ──
        $soundDept = Department::create([
            'church_id' => $church->id,
            'name' => 'Sound & AV',
            'description' => 'Audio-visual production, sound mixing, projection, and live streaming',
            'head' => $david->id,
            'is_active' => true,
        ]);

        $worshipDept = Department::create([
            'church_id' => $church->id,
            'name' => 'Worship Team',
            'description' => 'Musical worship, choir, band, and song selection',
            'head' => $maria->id,
            'is_active' => true,
        ]);

        $stageDept = Department::create([
            'church_id' => $church->id,
            'name' => 'Stage & Lighting',
            'description' => 'Stage setup, lighting design, props, and visual effects',
            'head' => $tom->id,
            'is_active' => true,
        ]);

        $hospitalityDept = Department::create([
            'church_id' => $church->id,
            'name' => 'Hospitality',
            'description' => 'Guest welcome, ushering, refreshments, and information desk',
            'head' => null,
            'is_active' => true,
        ]);

        $mediaDept = Department::create([
            'church_id' => $church->id,
            'name' => 'Media & Communications',
            'description' => 'Social media, photography, video production, and announcements',
            'head' => null,
            'is_active' => true,
        ]);

        // ── 4. Equipment Categories ──
        $audioCat = EquipmentCategory::create([
            'church_id' => $church->id,
            'name' => 'Audio Equipment',
            'description' => 'Microphones, mixers, speakers, monitors',
            'icon' => 'volume_up',
        ]);

        $visualCat = EquipmentCategory::create([
            'church_id' => $church->id,
            'name' => 'Visual Equipment',
            'description' => 'Projectors, screens, cameras, displays',
            'icon' => 'videocam',
        ]);

        $lightingCat = EquipmentCategory::create([
            'church_id' => $church->id,
            'name' => 'Lighting',
            'description' => 'Stage lights, LED panels, controllers',
            'icon' => 'lightbulb',
        ]);

        $itCat = EquipmentCategory::create([
            'church_id' => $church->id,
            'name' => 'IT & Networking',
            'description' => 'Laptops, routers, streaming equipment',
            'icon' => 'router',
        ]);

        // ── 5. Services ──
        $today = Carbon::today();

        $sundayService = Service::create([
            'church_id' => $church->id,
            'name' => 'Sunday Morning Worship',
            'service_date' => $today,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'service_type' => 'sunday_morning',
            'speaker' => 'Pastor James Mitchell',
            'worship_leader' => 'Maria Santos',
            'notes' => 'Communion Sunday – prepare elements. Special guest musician joining.',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $wednesdayService = Service::create([
            'church_id' => $church->id,
            'name' => 'Wednesday Night Prayer',
            'service_date' => $today->copy()->addDays(2),
            'start_time' => '19:00:00',
            'end_time' => '20:30:00',
            'service_type' => 'weeknight',
            'speaker' => 'Sarah Chen',
            'worship_leader' => 'David Kim',
            'notes' => 'Small group prayer night. Coffee and light refreshments.',
            'status' => 'draft',
            'created_by' => $sarah->id,
        ]);

        $youthService = Service::create([
            'church_id' => $church->id,
            'name' => 'Friday Youth Night',
            'service_date' => $today->copy()->addDays(4),
            'start_time' => '18:30:00',
            'end_time' => '20:00:00',
            'service_type' => 'youth',
            'speaker' => 'Tom Wilson',
            'worship_leader' => 'Emily Brown',
            'notes' => 'Youth group session. Games, worship, and message.',
            'status' => 'draft',
            'created_by' => $tom->id,
        ]);

        $christmasEve = Service::create([
            'church_id' => $church->id,
            'name' => 'Christmas Eve Candlelight',
            'service_date' => $today->copy()->addDays(14),
            'start_time' => '17:00:00',
            'end_time' => '18:30:00',
            'service_type' => 'special',
            'speaker' => 'Pastor James Mitchell',
            'worship_leader' => 'Maria Santos',
            'notes' => 'Candlelight service. 4:30 PM setup required. Choir performance.',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $easterService = Service::create([
            'church_id' => $church->id,
            'name' => 'Easter Celebration',
            'service_date' => $today->copy()->addDays(21),
            'start_time' => '09:00:00',
            'end_time' => '11:30:00',
            'service_type' => 'special',
            'speaker' => 'Pastor James Mitchell',
            'worship_leader' => 'Maria Santos',
            'notes' => 'Easter Sunday – multiple services. Full tech setup. Choir, orchestra.',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        // Past services
        $pastService1 = Service::create([
            'church_id' => $church->id,
            'name' => 'Sunday Morning Worship',
            'service_date' => $today->copy()->subWeek(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'service_type' => 'sunday_morning',
            'speaker' => 'Pastor James Mitchell',
            'worship_leader' => 'Maria Santos',
            'status' => 'completed',
            'created_by' => $admin->id,
        ]);

        $pastService2 = Service::create([
            'church_id' => $church->id,
            'name' => 'Baptism Sunday',
            'service_date' => $today->copy()->subDays(10),
            'start_time' => '10:00:00',
            'end_time' => '12:30:00',
            'service_type' => 'sunday_morning',
            'speaker' => 'Pastor James Mitchell',
            'worship_leader' => 'David Kim',
            'notes' => 'Special baptism service. Pool setup required.',
            'status' => 'completed',
            'created_by' => $admin->id,
        ]);

        // ── 6. Checklist Templates ──
        $soundTemplate = ChecklistTemplate::create([
            'church_id' => $church->id,
            'department_id' => $soundDept->id,
            'name' => 'Sunday Sound Check',
            'description' => 'Standard audio-visual checklist for Sunday morning services',
            'category' => 'audio',
            'is_global' => false,
            'is_active' => true,
        ]);

        ChecklistTemplateItem::insert([
            ['template_id' => $soundTemplate->id, 'title' => 'Test all wireless microphones', 'description' => 'Check battery levels and audio clarity for all 8 wireless mics', 'verification_type' => 'photo', 'is_required' => true, 'sort_order' => 1, 'estimated_minutes' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['template_id' => $soundTemplate->id, 'title' => 'Set up mixing console levels', 'description' => 'Reset and calibrate all channel levels on the main mixer', 'verification_type' => 'none', 'is_required' => true, 'sort_order' => 2, 'estimated_minutes' => 15, 'created_at' => now(), 'updated_at' => now()],
            ['template_id' => $soundTemplate->id, 'title' => 'Test monitoring system', 'description' => 'Verify all stage monitors and in-ear systems are working', 'verification_type' => 'photo', 'is_required' => true, 'sort_order' => 3, 'estimated_minutes' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['template_id' => $soundTemplate->id, 'title' => 'Verify live stream audio', 'description' => 'Confirm stream audio feed is clean and at correct levels', 'verification_type' => 'screenshot', 'is_required' => false, 'sort_order' => 4, 'estimated_minutes' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['template_id' => $soundTemplate->id, 'title' => 'Set up projection content', 'description' => 'Load all lyrics, slides, and media into ProPresenter', 'verification_type' => 'screenshot', 'is_required' => true, 'sort_order' => 5, 'estimated_minutes' => 10, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $stageTemplate = ChecklistTemplate::create([
            'church_id' => $church->id,
            'department_id' => $stageDept->id,
            'name' => 'Stage Setup',
            'description' => 'Physical stage preparation checklist',
            'category' => 'stage',
            'is_global' => false,
            'is_active' => true,
        ]);

        ChecklistTemplateItem::insert([
            ['template_id' => $stageTemplate->id, 'title' => 'Arrange stage furniture', 'description' => 'Set up pulpit, chairs, and instrument stands', 'verification_type' => 'photo', 'is_required' => true, 'sort_order' => 1, 'estimated_minutes' => 15, 'created_at' => now(), 'updated_at' => now()],
            ['template_id' => $stageTemplate->id, 'title' => 'Configure lighting scene', 'description' => 'Set up lighting for worship, message, and altar call', 'verification_type' => 'none', 'is_required' => true, 'sort_order' => 2, 'estimated_minutes' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['template_id' => $stageTemplate->id, 'title' => 'Check ambient temperature', 'description' => 'Ensure HVAC is set to comfortable level for congregation', 'verification_type' => 'none', 'is_required' => false, 'sort_order' => 3, 'estimated_minutes' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['template_id' => $stageTemplate->id, 'title' => 'Prepare communion elements', 'description' => 'Set up communion table with bread and juice (Communion Sundays)', 'verification_type' => 'photo', 'is_required' => false, 'sort_order' => 4, 'estimated_minutes' => 10, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $welcomeTemplate = ChecklistTemplate::create([
            'church_id' => $church->id,
            'department_id' => $hospitalityDept->id,
            'name' => 'Welcome Desk Setup',
            'description' => 'Guest hospitality and welcome preparation',
            'category' => 'hospitality',
            'is_global' => false,
            'is_active' => true,
        ]);

        ChecklistTemplateItem::insert([
            ['template_id' => $welcomeTemplate->id, 'title' => 'Stock welcome packets', 'description' => 'Ensure 50+ welcome packets are prepared and stocked', 'verification_type' => 'photo', 'is_required' => true, 'sort_order' => 1, 'estimated_minutes' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['template_id' => $welcomeTemplate->id, 'title' => 'Set up coffee station', 'description' => 'Brew coffee, prepare tea, set out cream and sugar', 'verification_type' => 'photo', 'is_required' => true, 'sort_order' => 2, 'estimated_minutes' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['template_id' => $welcomeTemplate->id, 'title' => 'Print bulletin inserts', 'description' => 'Print and collate weekly bulletin inserts', 'verification_type' => 'none', 'is_required' => false, 'sort_order' => 3, 'estimated_minutes' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['template_id' => $welcomeTemplate->id, 'title' => 'Brief volunteer team', 'description' => 'Hold 5-minute huddle with ushers and greeters', 'verification_type' => 'none', 'is_required' => true, 'sort_order' => 4, 'estimated_minutes' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── 7. Service Checklists + Items (for today's service) ──
        $soundChecklist = ServiceChecklist::create([
            'service_id' => $sundayService->id,
            'template_id' => $soundTemplate->id,
            'department_id' => $soundDept->id,
            'assigned_to' => $david->id,
            'status' => 'in_progress',
            'notes' => 'Start setup 45 min before service',
        ]);

        $soundItems = ChecklistTemplateItem::where('template_id', $soundTemplate->id)->get();
        foreach ($soundItems as $item) {
            $status = $item->sort_order <= 3 ? 'completed' : ($item->sort_order == 4 ? 'verified' : 'pending');
            ServiceChecklistItem::create([
                'checklist_id' => $soundChecklist->id,
                'template_item_id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'verification_type' => $item->verification_type,
                'is_required' => $item->is_required,
                'status' => $status,
                'completed_by' => $status == 'completed' || $status == 'verified' ? $david->id : null,
                'completed_at' => $status == 'completed' || $status == 'verified' ? now()->subMinutes(30) : null,
                'verified_by' => $status == 'verified' ? $admin->id : null,
                'verified_at' => $status == 'verified' ? now()->subMinutes(10) : null,
                'sort_order' => $item->sort_order,
            ]);
        }

        $stageChecklist = ServiceChecklist::create([
            'service_id' => $sundayService->id,
            'template_id' => $stageTemplate->id,
            'department_id' => $stageDept->id,
            'assigned_to' => $tom->id,
            'status' => 'completed',
        ]);

        $stageItems = ChecklistTemplateItem::where('template_id', $stageTemplate->id)->get();
        foreach ($stageItems as $item) {
            ServiceChecklistItem::create([
                'checklist_id' => $stageChecklist->id,
                'template_item_id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'verification_type' => $item->verification_type,
                'is_required' => $item->is_required,
                'status' => 'approved',
                'completed_by' => $tom->id,
                'completed_at' => now()->subHours(2),
                'verified_by' => $admin->id,
                'verified_at' => now()->subHours(1),
                'approved_by' => $admin->id,
                'approved_at' => now()->subMinutes(30),
                'sort_order' => $item->sort_order,
            ]);
        }

        $welcomeChecklist = ServiceChecklist::create([
            'service_id' => $sundayService->id,
            'template_id' => $welcomeTemplate->id,
            'department_id' => $hospitalityDept->id,
            'assigned_to' => $volunteers[0]->id,
            'status' => 'pending',
        ]);

        $welcomeItems = ChecklistTemplateItem::where('template_id', $welcomeTemplate->id)->get();
        foreach ($welcomeItems as $item) {
            ServiceChecklistItem::create([
                'checklist_id' => $welcomeChecklist->id,
                'template_item_id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'verification_type' => $item->verification_type,
                'is_required' => $item->is_required,
                'status' => 'pending',
                'sort_order' => $item->sort_order,
            ]);
        }

        // ── 8. Equipment ──
        $mixer = Equipment::create([
            'church_id' => $church->id,
            'category_id' => $audioCat->id,
            'department_id' => $soundDept->id,
            'name' => 'Yamaha TF3 Digital Mixer',
            'asset_id' => 'AST-AUD-001',
            'description' => '32-channel digital mixing console for main worship services',
            'brand' => 'Yamaha',
            'model' => 'TF3',
            'serial_number' => 'YAM-TF3-2023-001',
            'purchase_date' => '2023-06-15',
            'warranty_expires_at' => '2026-06-15',
            'purchase_price' => 4999.99,
            'status' => 'active',
            'qr_code' => 'EQ-MIXER-TF3',
            'location' => 'Main Sanctuary – Sound Booth',
            'last_maintenance_at' => $today->copy()->subMonths(2),
            'next_maintenance_at' => $today->copy()->addMonths(1),
            'created_by' => $david->id,
        ]);

        $projector = Equipment::create([
            'church_id' => $church->id,
            'category_id' => $visualCat->id,
            'department_id' => $soundDept->id,
            'name' => 'Epson Pro L1075U Laser Projector',
            'asset_id' => 'AST-VIS-001',
            'description' => '7000-lumen laser projector for main screen projection',
            'brand' => 'Epson',
            'model' => 'Pro L1075U',
            'serial_number' => 'EPS-L1075-2022-042',
            'purchase_date' => '2022-11-20',
            'warranty_expires_at' => '2025-11-20',
            'purchase_price' => 7200.00,
            'status' => 'active',
            'qr_code' => 'EQ-PROJ-L1075',
            'location' => 'Main Sanctuary – Ceiling Mount',
            'created_by' => $david->id,
        ]);

        $wirelessMics = Equipment::create([
            'church_id' => $church->id,
            'category_id' => $audioCat->id,
            'department_id' => $soundDept->id,
            'name' => 'Shure ULXD24Q Digital Wireless System (x4)',
            'asset_id' => 'AST-AUD-002',
            'description' => '4-channel wireless microphone system with handheld and lapel options',
            'brand' => 'Shure',
            'model' => 'ULXD24Q',
            'serial_number' => 'SHR-ULXD-2023-SET',
            'purchase_date' => '2023-03-10',
            'warranty_expires_at' => '2028-03-10',
            'purchase_price' => 6800.00,
            'status' => 'active',
            'qr_code' => 'EQ-MIC-ULXD',
            'location' => 'Main Sanctuary – Equipment Room',
            'created_by' => $david->id,
        ]);

        $stageMonitor = Equipment::create([
            'church_id' => $church->id,
            'category_id' => $audioCat->id,
            'department_id' => $soundDept->id,
            'name' => 'QSC K12.2 Powered Monitor',
            'asset_id' => 'AST-AUD-003',
            'description' => '12-inch powered stage monitor for performer foldback',
            'brand' => 'QSC',
            'model' => 'K12.2',
            'serial_number' => 'QSC-K122-2023-008',
            'purchase_date' => '2023-08-05',
            'warranty_expires_at' => '2028-08-05',
            'purchase_price' => 899.00,
            'status' => 'maintenance',
            'qr_code' => 'EQ-MON-K12',
            'location' => 'Main Sanctuary – Stage',
            'next_maintenance_at' => $today->copy()->addDays(3),
            'created_by' => $david->id,
        ]);

        $lightingBoard = Equipment::create([
            'church_id' => $church->id,
            'category_id' => $lightingCat->id,
            'department_id' => $stageDept->id,
            'name' => 'ETC ColorSource 20 Lighting Board',
            'asset_id' => 'AST-LIT-001',
            'description' => '20-channel LED lighting controller with scene programming',
            'brand' => 'ETC',
            'model' => 'ColorSource 20',
            'serial_number' => 'ETC-CS20-2022-015',
            'purchase_date' => '2022-09-12',
            'warranty_expires_at' => '2025-09-12',
            'purchase_price' => 1299.00,
            'status' => 'active',
            'qr_code' => 'EQ-LIGHT-CS20',
            'location' => 'Main Sanctuary – Tech Booth',
            'created_by' => $tom->id,
        ]);

        $laptop = Equipment::create([
            'church_id' => $church->id,
            'category_id' => $itCat->id,
            'department_id' => $soundDept->id,
            'name' => 'MacBook Pro 16" (OBS Streaming)',
            'asset_id' => 'AST-IT-001',
            'description' => 'Dedicated streaming laptop running OBS for live broadcast',
            'brand' => 'Apple',
            'model' => 'MacBook Pro 16"',
            'serial_number' => 'C02Z1234HKDD',
            'purchase_date' => '2024-01-15',
            'warranty_expires_at' => '2027-01-15',
            'purchase_price' => 2999.00,
            'status' => 'active',
            'qr_code' => 'EQ-LAPTOP-MBP',
            'location' => 'Main Sanctuary – Streaming Station',
            'created_by' => $david->id,
        ]);

        $camera = Equipment::create([
            'church_id' => $church->id,
            'category_id' => $visualCat->id,
            'department_id' => $mediaDept->id,
            'name' => 'Sony FX30 Cinema Camera',
            'asset_id' => 'AST-VIS-002',
            'description' => 'Primary camera for live stream and recording',
            'brand' => 'Sony',
            'model' => 'FX30',
            'serial_number' => 'SONY-FX30-2024-003',
            'purchase_date' => '2024-03-20',
            'warranty_expires_at' => '2027-03-20',
            'purchase_price' => 1800.00,
            'status' => 'faulty',
            'qr_code' => 'EQ-CAM-FX30',
            'location' => 'Main Sanctuary – Camera Platform',
            'created_by' => $david->id,
        ]);

        $wifiRouter = Equipment::create([
            'church_id' => $church->id,
            'category_id' => $itCat->id,
            'department_id' => $soundDept->id,
            'name' => 'Ubiquiti UniFi U6 Pro Access Point',
            'asset_id' => 'AST-IT-002',
            'description' => 'Enterprise WiFi access point for streaming and volunteer devices',
            'brand' => 'Ubiquiti',
            'model' => 'UniFi U6 Pro',
            'serial_number' => 'UBNT-U6P-2024-012',
            'purchase_date' => '2024-02-10',
            'warranty_expires_at' => '2029-02-10',
            'purchase_price' => 149.00,
            'status' => 'active',
            'qr_code' => 'EQ-WIFI-U6P',
            'location' => 'Main Sanctuary – Ceiling',
            'created_by' => $david->id,
        ]);

        // ── 9. Volunteer Schedules ──
        $schedules = [
            ['user' => $david, 'dept' => $soundDept, 'service' => $sundayService, 'date' => $today, 'start' => '09:00', 'end' => '12:30'],
            ['user' => $maria, 'dept' => $worshipDept, 'service' => $sundayService, 'date' => $today, 'start' => '09:30', 'end' => '12:00'],
            ['user' => $tom, 'dept' => $stageDept, 'service' => $sundayService, 'date' => $today, 'start' => '08:30', 'end' => '12:30'],
            ['user' => $volunteers[0], 'dept' => $hospitalityDept, 'service' => $sundayService, 'date' => $today, 'start' => '09:00', 'end' => '12:00'],
            ['user' => $volunteers[1], 'dept' => $worshipDept, 'service' => $sundayService, 'date' => $today, 'start' => '09:30', 'end' => '12:00'],
            ['user' => $volunteers[2], 'dept' => $soundDept, 'service' => $sundayService, 'date' => $today, 'start' => '09:00', 'end' => '12:30'],
            ['user' => $volunteers[3], 'dept' => $hospitalityDept, 'service' => $sundayService, 'date' => $today, 'start' => '09:00', 'end' => '12:00'],
            ['user' => $volunteers[4], 'dept' => $stageDept, 'service' => $sundayService, 'date' => $today, 'start' => '08:30', 'end' => '12:30'],
        ];

        foreach ($schedules as $s) {
            VolunteerSchedule::create([
                'user_id' => $s['user']->id,
                'department_id' => $s['dept']->id,
                'service_id' => $s['service']->id,
                'scheduled_date' => $s['date'],
                'start_time' => $s['start'],
                'end_time' => $s['end'],
                'status' => 'scheduled',
            ]);
        }

        // ── 10. Volunteer Attendance (today) ──
        foreach ([
            ['user' => $david, 'service' => $sundayService, 'status' => 'present', 'checkin' => now()->subHours(2)],
            ['user' => $tom, 'service' => $sundayService, 'status' => 'present', 'checkin' => now()->subHours(2)->subMinutes(15)],
            ['user' => $volunteers[0], 'service' => $sundayService, 'status' => 'late', 'checkin' => now()->subHours(1)],
            ['user' => $volunteers[2], 'service' => $sundayService, 'status' => 'present', 'checkin' => now()->subHours(2)->subMinutes(5)],
        ] as $a) {
            VolunteerAttendance::create([
                'user_id' => $a['user']->id,
                'service_id' => $a['service']->id,
                'check_in_time' => $a['checkin'],
                'status' => $a['status'],
            ]);
        }

        // ── 11. Incidents ──
        Incident::create([
            'church_id' => $church->id,
            'service_id' => $pastService1->id,
            'reported_by' => $david->id,
            'department_id' => $soundDept->id,
            'title' => 'Wireless mic #3 cutting out intermittently',
            'description' => 'During the second worship set, wireless handheld mic #3 started cutting out every 30-60 seconds. Battery was at 60%. May be an antenna issue.',
            'type' => 'equipment',
            'severity' => 'medium',
            'status' => 'resolved',
            'assigned_to' => $david->id,
            'resolved_by' => $david->id,
            'resolved_at' => now()->subDays(5),
            'resolution_notes' => 'Replaced the antenna cable. Tested for 2 hours with no issues.',
        ]);

        Incident::create([
            'church_id' => $church->id,
            'service_id' => null,
            'reported_by' => $volunteers[4]->id,
            'department_id' => $stageDept->id,
            'title' => 'Projector overheating during extended use',
            'description' => 'Epson projector displayed overheating warning after 2 hours of continuous use during the last event. Fan seems to be running at full speed.',
            'type' => 'equipment',
            'severity' => 'high',
            'status' => 'in_progress',
            'assigned_to' => $tom->id,
            'resolved_by' => null,
            'resolved_at' => null,
        ]);

        Incident::create([
            'church_id' => $church->id,
            'service_id' => null,
            'reported_by' => $volunteers[5]->id,
            'department_id' => $soundDept->id,
            'title' => 'WiFi dropping during live stream',
            'description' => 'WiFi connection drops every 15-20 minutes for about 30 seconds. This interrupts the live stream and affects streaming quality.',
            'type' => 'network',
            'severity' => 'high',
            'status' => 'open',
            'assigned_to' => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ]);

        // ── 12. Equipment Maintenance Logs ──
        EquipmentMaintenanceLog::create([
            'equipment_id' => $mixer->id,
            'performed_by' => $david->id,
            'type' => 'Preventive',
            'description' => 'Quarterly deep clean of all faders, knobs, and input jacks. Firmware updated to v5.1.',
            'cost' => 0.00,
            'performed_at' => $today->copy()->subMonths(2),
            'next_maintenance_at' => $today->copy()->addMonths(1),
        ]);

        EquipmentMaintenanceLog::create([
            'equipment_id' => $wirelessMics->id,
            'performed_by' => $david->id,
            'type' => 'Repair',
            'description' => 'Replaced antenna cable on channel 3. Re-synced all transmitters.',
            'cost' => 45.00,
            'performed_at' => $today->copy()->subDays(5),
            'next_maintenance_at' => $today->copy()->addMonths(3),
        ]);

        // ── 13. Equipment Fault Reports ──
        EquipmentFaultReport::create([
            'equipment_id' => $camera->id,
            'reported_by' => $david->id,
            'title' => 'Camera overheating and shutting down',
            'description' => 'Sony FX30 shuts down after 45 minutes of continuous recording. Thermal warning appears. This started after the last outdoor event.',
            'severity' => 'high',
            'status' => 'open',
        ]);

        EquipmentFaultReport::create([
            'equipment_id' => $stageMonitor->id,
            'reported_by' => $volunteers[4]->id,
            'title' => 'Distorted audio from left monitor',
            'description' => 'The left stage monitor produces distorted audio at volumes above 50%. Crackling sound at all frequencies.',
            'severity' => 'medium',
            'status' => 'in_progress',
            'resolved_by' => $david->id,
            'resolved_at' => $today->copy()->subDays(1),
            'resolution_notes' => 'Scheduled for driver replacement. Expected repair in 3 days.',
        ]);

        // ── 14. Notifications ──
        AppNotification::create([
            'user_id' => $admin->id,
            'type' => 'alert',
            'title' => 'Equipment Fault Reported',
            'message' => 'Sony FX30 camera has an overheating issue that needs attention.',
            'data' => ['equipment_id' => $camera->id],
            'read_at' => now(),
        ]);

        AppNotification::create([
            'user_id' => $admin->id,
            'type' => 'reminder',
            'title' => 'Service Coming Up',
            'message' => 'Sunday Morning Worship is scheduled for today. 3 checklists pending.',
            'data' => ['service_id' => $sundayService->id],
        ]);

        AppNotification::create([
            'user_id' => $david->id,
            'type' => 'task',
            'title' => 'Checklist Assigned',
            'message' => 'You have been assigned the Sunday Sound Check checklist for today\'s service.',
            'data' => ['checklist_id' => $soundChecklist->id],
        ]);

        echo "✅ Database seeded successfully!\n";
        echo "📧 Test login: test@example.com / password\n";
        echo "📧 Admin login: pastor@gracecommunity.church / password\n";
    }
}
