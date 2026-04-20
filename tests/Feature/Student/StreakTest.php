<?php

namespace Tests\Feature\Student;

use App\Models\DailyCheckin;
use App\Models\DailyStreak;
use App\Models\StudentProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class StreakTest extends TestCase
{
    use RefreshDatabase;

    private function createStudentWithProfile(): User
    {
        $user = User::factory()->student()->create();
        StudentProfile::create([
            'user_id' => $user->id,
            'school_name' => 'SMK Test',
            'bio' => 'Test bio for student streak test',
        ]);

        return $user;
    }

    private function authHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;

        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_can_get_streak_info(): void
    {
        $user = $this->createStudentWithProfile();

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/v1/student/streak');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['current_streak', 'longest_streak', 'last_checkin_date', 'checked_in_today'],
            ])
            ->assertJson([
                'data' => [
                    'current_streak' => 0,
                    'checked_in_today' => false,
                ],
            ]);
    }

    public function test_can_checkin(): void
    {
        $user = $this->createStudentWithProfile();

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/v1/student/streak/checkin', [
                'description' => 'Hari ini saya belajar dasar-dasar Node.js dan REST API',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'current_streak' => 1,
                    'checked_in_today' => true,
                ],
            ]);

        $this->assertDatabaseHas('daily_checkins', [
            'user_id' => $user->id,
            'checkin_date' => Carbon::today()->toDateString(),
        ]);
    }

    public function test_cannot_checkin_twice_same_day(): void
    {
        $user = $this->createStudentWithProfile();

        // First check-in
        $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/v1/student/streak/checkin', [
                'description' => 'First check-in of the day today',
            ]);

        // Second check-in
        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/v1/student/streak/checkin', [
                'description' => 'Second attempt at checking in today',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'ALREADY_CHECKED_IN',
            ]);
    }

    public function test_consecutive_days_increment_streak(): void
    {
        $user = $this->createStudentWithProfile();

        // Yesterday's check-in
        DailyStreak::create([
            'user_id' => $user->id,
            'current_streak' => 5,
            'longest_streak' => 10,
            'last_checkin_date' => Carbon::yesterday(),
        ]);
        DailyCheckin::create([
            'user_id' => $user->id,
            'description' => 'Yesterday activity log',
            'checkin_date' => Carbon::yesterday(),
        ]);

        // Today's check-in
        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/v1/student/streak/checkin', [
                'description' => 'Continuing my learning streak today!',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'current_streak' => 6,
                    'longest_streak' => 10,
                ],
            ]);
    }

    public function test_skipped_day_resets_streak(): void
    {
        $user = $this->createStudentWithProfile();

        // 2 days ago check-in (skipped yesterday)
        DailyStreak::create([
            'user_id' => $user->id,
            'current_streak' => 5,
            'longest_streak' => 10,
            'last_checkin_date' => Carbon::today()->subDays(2),
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/v1/student/streak/checkin', [
                'description' => 'Back to learning after missing a day',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'current_streak' => 1, // Reset
                    'longest_streak' => 10, // Preserved
                ],
            ]);
    }

    public function test_checkin_description_too_short_fails(): void
    {
        $user = $this->createStudentWithProfile();

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/v1/student/streak/checkin', [
                'description' => 'short',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_get_checkin_history(): void
    {
        $user = $this->createStudentWithProfile();

        DailyCheckin::create([
            'user_id' => $user->id,
            'description' => 'Day 1 of learning journey recap',
            'checkin_date' => Carbon::today()->subDays(2),
        ]);
        DailyCheckin::create([
            'user_id' => $user->id,
            'description' => 'Day 2 of learning journey recap',
            'checkin_date' => Carbon::yesterday(),
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/v1/student/streak/history');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
            ]);
    }
}
