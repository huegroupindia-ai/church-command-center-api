<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Department;
use App\Models\Service;
use App\Models\User;
use App\Models\VolunteerAttendance;
use App\Models\VolunteerSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VolunteerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Church $church;
    private Service $service;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->church = Church::create([
            'name' => 'Test Church',
            'slug' => 'test-church',
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'church_id' => $this->church->id,
            'name' => 'Worship',
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

    private function createVolunteer(string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@test.com',
            'password' => Hash::make('password'),
            'role' => 'volunteer',
            'church_id' => $this->church->id,
            'is_active' => true,
        ]);
    }

    public function test_create_volunteer_schedule(): void
    {
        $volunteer = $this->createVolunteer('Alex Test');

        $response = $this->postJson('/api/v1/volunteer-schedules', [
            'user_id' => $volunteer->id,
            'department_id' => $this->department->id,
            'service_id' => $this->service->id,
            'scheduled_date' => Carbon::today()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'status' => 'scheduled',
        ], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertStatus(201);
    }

    public function test_check_in_volunteer(): void
    {
        $volunteer = $this->createVolunteer('Checkin Test');

        $attendance = VolunteerAttendance::create([
            'user_id' => $volunteer->id,
            'service_id' => $this->service->id,
            'status' => 'absent',
        ]);

        $response = $this->postJson("/api/v1/volunteer-attendance/{$attendance->id}/check-in", [], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'present']);

        $this->assertDatabaseHas('volunteer_attendance', [
            'id' => $attendance->id,
            'status' => 'present',
        ]);

        $this->assertNotNull($attendance->fresh()->check_in_time);
    }

    public function test_check_out_volunteer(): void
    {
        $volunteer = $this->createVolunteer('Checkout Test');

        $attendance = VolunteerAttendance::create([
            'user_id' => $volunteer->id,
            'service_id' => $this->service->id,
            'check_in_time' => now()->subHours(2),
            'status' => 'present',
        ]);

        $response = $this->postJson("/api/v1/volunteer-attendance/{$attendance->id}/check-out", [], [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'completed']);

        $this->assertDatabaseHas('volunteer_attendance', [
            'id' => $attendance->id,
            'status' => 'completed',
        ]);
    }

    public function test_list_volunteer_attendance(): void
    {
        $volunteer = $this->createVolunteer('List Test');

        VolunteerAttendance::create([
            'user_id' => $volunteer->id,
            'service_id' => $this->service->id,
            'check_in_time' => now(),
            'status' => 'present',
        ]);

        $response = $this->getJson('/api/v1/volunteer-attendance', [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_attendance_by_service(): void
    {
        $volunteer = $this->createVolunteer('Filter Test');

        $service2 = Service::create([
            'church_id' => $this->church->id,
            'name' => 'Other Service',
            'service_date' => Carbon::today()->addDays(7),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        VolunteerAttendance::create([
            'user_id' => $volunteer->id,
            'service_id' => $this->service->id,
            'check_in_time' => now(),
            'status' => 'present',
        ]);

        VolunteerAttendance::create([
            'user_id' => $volunteer->id,
            'service_id' => $service2->id,
            'status' => 'absent',
        ]);

        $response = $this->getJson("/api/v1/volunteer-attendance?service_id={$this->service->id}", [
            'headers' => $this->authHeaders(),
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
