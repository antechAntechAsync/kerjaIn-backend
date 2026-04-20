<?php

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;

        return ['Authorization' => "Bearer {$token}"];
    }

    // ===== Complete Profile =====

    public function test_student_can_complete_profile(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'is_profile_completed' => false,
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/v1/complete-profile', [
                'name' => 'John Doe',
                'phone_number' => '08123456789',
                'industry' => 'Technology',
                'linkedin_url' => 'https://linkedin.com/in/john',
                'school_name' => 'SMK Negeri 1 Jakarta',
                'bio' => 'Passionate about web development and backend engineering',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Profile completed successfully',
                'data' => ['is_profile_completed' => true],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_profile_completed' => true,
            'industry' => 'Technology',
        ]);

        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $user->id,
            'school_name' => 'SMK Negeri 1 Jakarta',
        ]);
    }

    public function test_professional_can_complete_profile(): void
    {
        $user = User::factory()->create([
            'role' => 'professional',
            'is_profile_completed' => false,
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/v1/complete-profile', [
                'name' => 'Jane Smith',
                'phone_number' => '08567891234',
                'industry' => 'Technology',
                'company_name' => 'PT Tech Indonesia',
                'social_media_links' => [
                    'twitter' => 'https://twitter.com/jane',
                ],
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['is_profile_completed' => true],
            ]);

        $this->assertDatabaseHas('professional_profiles', [
            'user_id' => $user->id,
            'company_name' => 'PT Tech Indonesia',
        ]);
    }

    public function test_cannot_complete_profile_twice(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'is_profile_completed' => true,
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/v1/complete-profile', [
                'name' => 'John',
                'phone_number' => '08123456789',
                'industry' => 'Tech',
                'school_name' => 'SMK 1',
                'bio' => 'Some bio that is at least ten characters',
            ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_student_profile_validation(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'is_profile_completed' => false,
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/v1/complete-profile', [
                'name' => 'J', // too short
                // missing required fields
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    // ===== Get Profile =====

    public function test_can_get_own_profile(): void
    {
        $user = User::factory()->student()->create();
        StudentProfile::create([
            'user_id' => $user->id,
            'school_name' => 'SMK Test',
            'bio' => 'Test bio lorem ipsum',
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/v1/profile');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['user', 'profile'],
            ]);
    }

    // ===== Update Profile =====

    public function test_can_update_profile(): void
    {
        $user = User::factory()->student()->create();
        StudentProfile::create([
            'user_id' => $user->id,
            'school_name' => 'SMK Test',
            'bio' => 'Original bio lorem ipsum dolor sit amet',
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->putJson('/api/v1/profile', [
                'name' => 'Updated Name',
                'phone_number' => '08999999999',
                'industry' => 'Finance',
                'bio' => 'Updated bio lorem ipsum dolor sit amet',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'industry' => 'Finance',
        ]);
    }
}
