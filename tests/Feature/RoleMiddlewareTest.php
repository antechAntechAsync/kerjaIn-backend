<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;

        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_student_can_access_student_routes(): void
    {
        $user = User::factory()->student()->create();

        // Student dashboard should be accessible (even if it may return 404 for missing data)
        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/v1/student/dashboard');

        // Should NOT be 401 or 403
        $this->assertNotEquals(401, $response->getStatusCode());
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_professional_cannot_access_student_routes(): void
    {
        $user = User::factory()->professional()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/v1/student/dashboard');

        // Should be forbidden
        $response->assertStatus(403);
    }

    public function test_student_cannot_access_professional_routes(): void
    {
        $user = User::factory()->student()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/v1/professional/dashboard');

        $response->assertStatus(403);
    }

    public function test_incomplete_profile_blocked_from_student_routes(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'is_profile_completed' => false,
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/v1/student/dashboard');

        $response->assertStatus(403)
            ->assertJson([
                'error_code' => 'PROFILE_INCOMPLETE',
            ]);
    }

    public function test_unauthenticated_cannot_access_any_route(): void
    {
        $response = $this->getJson('/api/v1/student/dashboard');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/professional/dashboard');
        $response->assertStatus(401);
    }
}
