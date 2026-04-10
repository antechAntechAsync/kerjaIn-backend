<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'portfolio_id',
        'title',
        'description',
        'project_type',
        'github_url',
        'demo_url',
        'thumbnail_url',
        'complexity_level',
        'is_featured',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    public function portfolio()
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function skills()
    {
        return $this->hasMany(ProjectSkill::class);
    }

    public function media()
    {
        return $this->hasMany(ProjectMedia::class);
    }

    public function getComplexityScoreAttribute()
    {
        return match ($this->complexity_level) {
            'easy' => 1,
            'medium' => 2,
            'hard' => 3,
            default => 1,
        };
    }
}
