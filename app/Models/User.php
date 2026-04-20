<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable, SoftDeletes;

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
        'google_id',
        'provider',
        'avatar',
        'phone_number',
        'industry',
        'linkedin_url',
        'is_profile_completed',
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

    // ========================================================================
    // Role Checks
    // ========================================================================

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isProfessional(): bool
    {
        return $this->role === 'professional' || $this->role === 'hr';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // ========================================================================
    // Relationships
    // ========================================================================

    public function studentProfile()
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function professionalProfile()
    {
        return $this->hasOne(ProfessionalProfile::class);
    }

    public function activeRoadmap()
    {
        return $this->hasOne(UserRoadmap::class)
            ->where('is_active', true)
            ->latest();
    }

    public function interestResults()
    {
        return $this->hasMany(InterestResult::class, 'student_id');
    }

    public function careerRecommendations()
    {
        return $this->hasMany(CareerRecommendation::class, 'student_id');
    }

    public function portfolios()
    {
        return $this->hasMany(Portfolio::class);
    }

    public function skillScores()
    {
        return $this->hasMany(UserSkillScore::class);
    }

    public function dailyStreak()
    {
        return $this->hasOne(DailyStreak::class);
    }

    public function dailyCheckins()
    {
        return $this->hasMany(DailyCheckin::class);
    }

    public function jobApplications()
    {
        return $this->hasMany(JobApplication::class);
    }

    public function jobListings()
    {
        return $this->hasMany(JobListing::class);
    }

    // ========================================================================
    // Casts
    // ========================================================================

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
            'is_profile_completed' => 'boolean',
        ];
    }
}
