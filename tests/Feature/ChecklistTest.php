<?php

namespace Tests\Feature;

use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateItem;
use App\Models\Church;
use App\Models\Service;
use App\Models\ServiceChecklist;
use App\Models\ServiceChecklistItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChecklistTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Church $church;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->church = Church::create([
            'name' => 'Test Church',
            'slug' => 'test-church',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'church_id' => $this->church->id,
            'is_active' => true,
        ]);

        $this->service = Service::create([
            'church_id' => $this->church->id,
            'name' => 'Sunday Service',
            'service_date' => Carbon::today(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $this->token = $response->json('access_token');
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_create_checklist_template(): void
    {
        $response = $this->postJson('/api/v1/checklist-templates', [
            'church_id' => $this->church->id,
            'name' => 'Sunday Setup',
            'description' => 'Standard Sunday checklist',
            'category' => 'general',
        ], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'Sunday Setup',
            ]);
    }

    public function test_create_service_checklist(): void
    {
        $response = $this->postJson('/api/v1/service-checklists', [
            'service_id' => $this->service->id,
            'assigned_to' => $this->user->id,
            'status' => 'pending',
        ], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertStatus(201);
    }

    public function test_create_checklist_item(): void
    {
        $checklist = ServiceChecklist::create([
            'service_id' => $this->service->id,
            'assigned_to' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/v1/service-checklist-items', [
            'checklist_id' => $checklist->id,
            'title' => 'Test microphones',
            'description' => 'Check all wireless mics',
            'verification_type' => 'photo',
            'is_required' => true,
        ], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'title' => 'Test microphones',
                'status' => 'pending',
            ]);
    }

    public function test_complete_checklist_item(): void
    {
        $checklist = ServiceChecklist::create([
            'service_id' => $this->service->id,
            'assigned_to' => $this->user->id,
            'status' => 'in_progress',
        ]);

        $item = ServiceChecklistItem::create([
            'checklist_id' => $checklist->id,
            'title' => 'Test item',
            'status' => 'pending',
            'sort_order' => 1,
        ]);

        $response = $this->postJson("/api/v1/service-checklist-items/{$item->id}/complete", [], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'completed']);

        $this->assertDatabaseHas('service_checklist_items', [
            'id' => $item->id,
            'status' => 'completed',
            'completed_by' => $this->user->id,
        ]);
    }

    public function test_verify_checklist_item(): void
    {
        $checklist = ServiceChecklist::create([
            'service_id' => $this->service->id,
            'assigned_to' => $this->user->id,
            'status' => 'in_progress',
        ]);

        $item = ServiceChecklistItem::create([
            'checklist_id' => $checklist->id,
            'title' => 'To Verify',
            'status' => 'completed',
            'completed_by' => $this->user->id,
            'completed_at' => now(),
            'sort_order' => 1,
        ]);

        $response = $this->postJson("/api/v1/service-checklist-items/{$item->id}/verify", [], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'verified']);
    }

    public function test_approve_checklist_item(): void
    {
        $checklist = ServiceChecklist::create([
            'service_id' => $this->service->id,
            'assigned_to' => $this->user->id,
            'status' => 'in_progress',
        ]);

        $item = ServiceChecklistItem::create([
            'checklist_id' => $checklist->id,
            'title' => 'To Approve',
            'status' => 'verified',
            'verified_by' => $this->user->id,
            'verified_at' => now(),
            'sort_order' => 1,
        ]);

        $response = $this->postJson("/api/v1/service-checklist-items/{$item->id}/approve", [], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'approved']);
    }

    public function test_full_workflow_pending_to_approved(): void
    {
        $checklist = ServiceChecklist::create([
            'service_id' => $this->service->id,
            'assigned_to' => $this->user->id,
            'status' => 'in_progress',
        ]);

        $item = ServiceChecklistItem::create([
            'checklist_id' => $checklist->id,
            'title' => 'Full Workflow Item',
            'status' => 'pending',
            'sort_order' => 1,
        ]);

        // Step 1: Complete
        $response = $this->postJson("/api/v1/service-checklist-items/{$item->id}/complete", [], [
            'headers' => $this->authHeaders(),
        ]);
        $response->assertOk()->assertJson(['status' => 'completed']);

        // Step 2: Verify
        $response = $this->postJson("/api/v1/service-checklist-items/{$item->id}/verify", [], [
            'headers' => $this->authHeaders(),
        ]);
        $response->assertOk()->assertJson(['status' => 'verified']);

        // Step 3: Approve
        $response = $this->postJson("/api/v1/service-checklist-items/{$item->id}/approve", [], [
            'headers' => $this->authHeaders(),
        ]);
        $response->assertOk()->assertJson(['status' => 'approved']);

        // Verify final state
        $this->assertDatabaseHas('service_checklist_items', [
            'id' => $item->id,
            'status' => 'approved',
            'completed_by' => $this->user->id,
            'verified_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);
    }

    public function test_delete_checklist_item(): void
    {
        $checklist = ServiceChecklist::create([
            'service_id' => $this->service->id,
            'assigned_to' => $this->user->id,
            'status' => 'pending',
        ]);

        $item = ServiceChecklistItem::create([
            'checklist_id' => $checklist->id,
            'title' => 'To Delete',
            'status' => 'pending',
            'sort_order' => 1,
        ]);

        $response = $this->deleteJson("/api/v1/service-checklist-items/{$item->id}", [], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('service_checklist_items', ['id' => $item->id]);
    }
}
