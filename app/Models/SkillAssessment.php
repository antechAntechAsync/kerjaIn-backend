<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillAssessment extends Model
{
    protected $fillable = [
        'user_id',
        'role',
        'level',
        'score',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(SkillAssessmentAnswer::class, 'assessment_id');
    }
}
