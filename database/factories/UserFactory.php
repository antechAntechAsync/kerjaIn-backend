<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'student',
            'is_profile_completed' => false,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a student with completed profile.
     */
    public function student(): static
    {
        return $this->state(fn () => [
            'role' => 'student',
            'is_profile_completed' => true,
            'phone_number' => fake()->phoneNumber(),
            'industry' => 'Technology',
            'linkedin_url' => 'https://linkedin.com/in/' . fake()->userName(),
        ]);
    }

    /**
     * Create a professional with completed profile.
     */
    public function professional(): static
    {
        return $this->state(fn () => [
            'role' => 'professional',
            'is_profile_completed' => true,
            'phone_number' => fake()->phoneNumber(),
            'industry' => 'Technology',
            'linkedin_url' => 'https://linkedin.com/in/' . fake()->userName(),
        ]);
    }

    /**
     * Create an admin user.
     */
    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => 'admin',
            'is_profile_completed' => true,
        ]);
    }

    /**
     * Mark profile as completed.
     */
    public function withCompletedProfile(): static
    {
        return $this->state(fn () => [
            'is_profile_completed' => true,
            'phone_number' => fake()->phoneNumber(),
            'industry' => 'Technology',
        ]);
    }
}
