<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IncidentTest extends TestCase
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

    public function test_create_incident(): void
    {
        $response = $this->postJson('/api/v1/incidents', [
            'title' => 'Mic feedback issue',
            'description' => 'High-pitched feedback from main speaker during worship',
            'type' => 'equipment',
            'severity' => 'medium',
            'status' => 'open',
        ], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'title' => 'Mic feedback issue',
                'severity' => 'medium',
                'status' => 'open',
            ]);

        $this->assertDatabaseHas('incidents', [
            'title' => 'Mic feedback issue',
            'reported_by' => $this->user->id,
        ]);
    }

    public function test_list_incidents(): void
    {
        Incident::create([
            'church_id' => $this->church->id,
            'reported_by' => $this->user->id,
            'title' => 'Incident 1',
            'description' => 'Details',
            'status' => 'open',
        ]);

        Incident::create([
            'church_id' => $this->church->id,
            'reported_by' => $this->user->id,
            'title' => 'Incident 2',
            'description' => 'Details',
            'status' => 'resolved',
        ]);

        $response = $this->getJson('/api/v1/incidents', [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_filter_incidents_by_status(): void
    {
        Incident::create([
            'church_id' => $this->church->id,
            'reported_by' => $this->user->id,
            'title' => 'Open',
            'description' => 'Details',
            'status' => 'open',
        ]);

        Incident::create([
            'church_id' => $this->church->id,
            'reported_by' => $this->user->id,
            'title' => 'Resolved',
            'description' => 'Details',
            'status' => 'resolved',
        ]);

        $response = $this->getJson('/api/v1/incidents?status=open', [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Open');
    }

    public function test_update_incident_status(): void
    {
        $incident = Incident::create([
            'church_id' => $this->church->id,
            'reported_by' => $this->user->id,
            'title' => 'To Resolve',
            'description' => 'Details',
            'severity' => 'high',
            'status' => 'open',
        ]);

        $response = $this->patchJson("/api/v1/incidents/{$incident->id}/status", [
            'status' => 'resolved',
            'comments' => 'Fixed the issue',
        ], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'resolved']);

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => 'resolved',
        ]);
    }

    public function test_delete_incident(): void
    {
        $incident = Incident::create([
            'church_id' => $this->church->id,
            'reported_by' => $this->user->id,
            'title' => 'To Delete',
            'description' => 'Details',
            'status' => 'open',
        ]);

        $response = $this->deleteJson("/api/v1/incidents/{$incident->id}", [], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('incidents', ['id' => $incident->id]);
    }

    public function test_incident_reporter_is_set_automatically(): void
    {
        $response = $this->postJson('/api/v1/incidents', [
            'title' => 'Auto reporter',
            'description' => 'Should set reported_by',
        ], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertStatus(201);

        $incident = Incident::where('title', 'Auto reporter')->first();
        $this->assertEquals($this->user->id, $incident->reported_by);
    }
}
