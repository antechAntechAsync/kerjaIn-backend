<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillAssessmentAnswer extends Model
{
    protected $fillable = [
        'assessment_id',
        'question',
        'answer',
        'score',
    ];

    public function assessment()
    {
        return $this->belongsTo(SkillAssessment::class, 'assessment_id');
    }
}
