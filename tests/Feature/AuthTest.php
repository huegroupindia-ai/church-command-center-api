<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ], $overrides));
    }

    private function loginAs(User $user): string
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        return $response->json('access_token');
    }

    // ── Login Tests ──

    public function test_login_with_valid_credentials_returns_token(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'user' => ['id', 'name', 'email', 'role'],
            ])
            ->assertJson([
                'token_type' => 'bearer',
                'user' => [
                    'email' => 'test@example.com',
                    'role' => 'admin',
                ],
            ]);

        // Verify user record was updated
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_login_with_invalid_password_returns_422(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_with_nonexistent_email_returns_422(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_without_email_returns_422(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_without_password_returns_422(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    // ── Register Tests ──

    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => ['id', 'name', 'email', 'role'],
            ])
            ->assertJson([
                'user' => [
                    'name' => 'New User',
                    'email' => 'new@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'name' => 'New User',
        ]);
    }

    public function test_register_with_duplicate_email_returns_422(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Duplicate',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_with_short_password_returns_422(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Short',
            'email' => 'short@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_with_mismatched_passwords_returns_422(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Mismatch',
            'email' => 'mismatch@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertStatus(422);
    }

    // ── Me (Profile) Tests ──

    public function test_me_returns_authenticated_user(): void
    {
        $user = $this->createUser();
        $token = $this->loginAs($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJson([
                'id' => $user->id,
                'name' => 'Test User',
                'email' => 'test@example.com',
                'role' => 'admin',
            ]);
    }

    public function test_me_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401);
    }

    // ── Logout Tests ──

    public function test_logout_invalidates_token(): void
    {
        $user = $this->createUser();
        $token = $this->loginAs($user);

        // Should work
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/logout');
        $response->assertOk();

        // Token should be invalid now
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/me');
        $response->assertStatus(401);
    }

    // ── Protected Route Tests ──

    public function test_protected_routes_require_authentication(): void
    {
        $protectedEndpoints = [
            ['GET', '/api/v1/dashboard'],
            ['GET', '/api/v1/services'],
            ['GET', '/api/v1/equipment'],
            ['GET', '/api/v1/incidents'],
            ['GET', '/api/v1/volunteer-attendance'],
            ['GET', '/api/v1/users'],
        ];

        foreach ($protectedEndpoints as [$method, $uri]) {
            $response = $this->json($method, $uri);
            $response->assertStatus(401, "Expected 401 for unauthenticated $method $uri");
        }
    }
}
