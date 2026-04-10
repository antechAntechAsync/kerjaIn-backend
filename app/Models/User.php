<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'role_name',
        'google_id',
        'provider',
        'avatar',
        'status',
        'join_date',
        'last_login',
        'user_id',
        'phone_number',
        'position',
        'department',
        'line_manager',
        'seconde_line_manager',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function activeRoadmap()
    {
        return $this->hasOne(UserRoadmap::class)
            ->where('is_active', true)
            ->latest();
    }

    public function profile()
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function interestResults()
    {
        return $this->hasMany(InterestResult::class, 'student_id');
    }

    public function careerRecommendations()
    {
        return $this->hasMany(CareerRecommendation::class, 'student_id');
    }
}
