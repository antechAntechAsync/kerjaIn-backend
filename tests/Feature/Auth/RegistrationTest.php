<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_register(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'student',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user', 'token', 'is_profile_completed'],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_profile_completed' => false,
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'student@example.com',
            'role' => 'student',
            'is_profile_completed' => false,
        ]);
    }

    public function test_professional_can_register(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test Professional',
            'email' => 'pro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'professional',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => ['is_profile_completed' => false],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'pro@example.com',
            'role' => 'professional',
        ]);
    }

    public function test_registration_fails_with_invalid_role(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'student',
        ]);

        $response->assertStatus(422);
    }

    public function test_registration_fails_with_short_password(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
            'role' => 'student',
        ]);

        $response->assertStatus(422);
    }
}
