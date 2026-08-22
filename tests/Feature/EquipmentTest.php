<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentFaultReport;
use App\Models\EquipmentMaintenanceLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EquipmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Church $church;

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

    public function test_list_equipment(): void
    {
        $category = EquipmentCategory::create([
            'church_id' => $this->church->id,
            'name' => 'Audio',
        ]);

        Equipment::create([
            'church_id' => $this->church->id,
            'category_id' => $category->id,
            'name' => 'Mixer',
            'asset_id' => 'EQ-001',
            'qr_code' => 'EQ-MIXER-001',
            'created_by' => $this->user->id,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/equipment', [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_create_equipment(): void
    {
        $category = EquipmentCategory::create([
            'church_id' => $this->church->id,
            'name' => 'Visual',
        ]);

        $response = $this->postJson('/api/v1/equipment', [
            'name' => 'New Projector',
            'category_id' => $category->id,
            'asset_id' => 'EQ-PROJ-001',
            'brand' => 'Epson',
            'model' => 'Pro L1075U',
            'qr_code' => 'EQ-PROJ-001',
            'status' => 'active',
        ], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'New Projector',
                'brand' => 'Epson',
                'status' => 'active',
            ]);
    }

    public function test_create_equipment_fault_report(): void
    {
        $category = EquipmentCategory::create([
            'church_id' => $this->church->id,
            'name' => 'Audio',
        ]);

        $equipment = Equipment::create([
            'church_id' => $this->church->id,
            'category_id' => $category->id,
            'name' => 'Broken Speaker',
            'asset_id' => 'EQ-SPK-001',
            'qr_code' => 'EQ-SPK-001',
            'created_by' => $this->user->id,
            'status' => 'faulty',
        ]);

        $response = $this->postJson('/api/v1/equipment-faults', [
            'equipment_id' => $equipment->id,
            'title' => 'Speaker crackling',
            'description' => 'The speaker crackles at high volume',
            'severity' => 'high',
        ], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'title' => 'Speaker crackling',
                'severity' => 'high',
                'status' => 'open',
            ]);
    }

    public function test_update_equipment_fault_status(): void
    {
        $category = EquipmentCategory::create([
            'church_id' => $this->church->id,
            'name' => 'Audio',
        ]);

        $equipment = Equipment::create([
            'church_id' => $this->church->id,
            'category_id' => $category->id,
            'name' => 'Speaker',
            'asset_id' => 'EQ-SPK-002',
            'qr_code' => 'EQ-SPK-002',
            'created_by' => $this->user->id,
            'status' => 'faulty',
        ]);

        $fault = EquipmentFaultReport::create([
            'equipment_id' => $equipment->id,
            'reported_by' => $this->user->id,
            'title' => 'Issue',
            'description' => 'Details',
            'severity' => 'medium',
            'status' => 'open',
        ]);

        $response = $this->patchJson("/api/v1/equipment-faults/{$fault->id}/status", [
            'status' => 'resolved',
        ], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'resolved']);
    }

    public function test_equipment_categories_crud(): void
    {
        // Create
        $response = $this->postJson('/api/v1/equipment-categories', [
            'name' => 'Lighting',
            'description' => 'Stage lights and controllers',
        ], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertStatus(201);
        $categoryId = $response->json('id');

        // List
        $response = $this->getJson('/api/v1/equipment-categories', [
            'headers' => $this->authHeaders(),
        ]);
        $response->assertOk()->assertJsonCount(1, 'data');

        // Show
        $response = $this->getJson("/api/v1/equipment-categories/$categoryId", [
            'headers' => $this->authHeaders(),
        ]);
        $response->assertOk()
            ->assertJson(['name' => 'Lighting']);

        // Delete
        $response = $this->deleteJson("/api/v1/equipment-categories/$categoryId", [], [
            'headers' => $this->authHeaders(),
        ]);
        $response->assertOk();
        $this->assertDatabaseMissing('equipment_categories', ['id' => $categoryId]);
    }
}
