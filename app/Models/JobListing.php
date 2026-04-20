<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobListing extends Model
{
    protected $table = 'job_listings';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'employment_type',
        'site_type',
        'industry',
        'location',
        'required_skills',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'required_skills' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Backward compat: alias for user().
     */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    // Keep old relation for backward compat
    public function requirements()
    {
        return $this->hasMany(JobSkillRequirement::class, 'job_listing_id');
    }

    // Legacy: keep 'skills' relation name
    public function skills()
    {
        return $this->requirements();
    }
}
