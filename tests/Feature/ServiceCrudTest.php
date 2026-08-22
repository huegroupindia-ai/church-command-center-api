<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ServiceCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $church = Church::create([
            'name' => 'Test Church',
            'slug' => 'test-church',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'church_id' => $church->id,
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

    public function test_list_services(): void
    {
        Service::create([
            'church_id' => $this->user->church_id,
            'name' => 'Sunday Worship',
            'service_date' => now()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/services', [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_create_service(): void
    {
        $response = $this->postJson('/api/v1/services', [
            'name' => 'Easter Service',
            'service_date' => '2026-04-05',
            'start_time' => '09:00',
            'end_time' => '11:30',
            'service_type' => 'special',
            'speaker' => 'Pastor John',
            'status' => 'draft',
        ], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id', 'name', 'service_date', 'start_time', 'end_time', 'status',
            ])
            ->assertJson([
                'name' => 'Easter Service',
                'service_type' => 'special',
            ]);

        $this->assertDatabaseHas('services', [
            'name' => 'Easter Service',
            'status' => 'draft',
        ]);
    }

    public function test_show_service(): void
    {
        $service = Service::create([
            'church_id' => $this->user->church_id,
            'name' => 'Christmas Eve',
            'service_date' => '2026-12-24',
            'start_time' => '17:00',
            'end_time' => '19:00',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/services/{$service->id}", [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk()
            ->assertJson([
                'id' => $service->id,
                'name' => 'Christmas Eve',
            ]);
    }

    public function test_update_service(): void
    {
        $service = Service::create([
            'church_id' => $this->user->church_id,
            'name' => 'Original Name',
            'service_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $response = $this->putJson("/api/v1/services/{$service->id}", [
            'name' => 'Updated Name',
            'speaker' => 'Guest Speaker',
        ], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk()
            ->assertJson([
                'name' => 'Updated Name',
                'speaker' => 'Guest Speaker',
            ]);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_update_service_status(): void
    {
        $service = Service::create([
            'church_id' => $this->user->church_id,
            'name' => 'Status Test',
            'service_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $response = $this->patchJson("/api/v1/services/{$service->id}/status", [
            'status' => 'active',
        ], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'active']);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'status' => 'active',
        ]);
    }

    public function test_delete_service(): void
    {
        $service = Service::create([
            'church_id' => $this->user->church_id,
            'name' => 'To Delete',
            'service_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/v1/services/{$service->id}", [], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_filter_services_by_status(): void
    {
        Service::create([
            'church_id' => $this->user->church_id,
            'name' => 'Draft Service',
            'service_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        Service::create([
            'church_id' => $this->user->church_id,
            'name' => 'Active Service',
            'service_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/services?status=draft', [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Draft Service');
    }
}
