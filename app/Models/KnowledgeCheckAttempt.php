<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeCheckAttempt extends Model
{
    protected $fillable = [
        'session_id',
        'question_id',
        'selected_answer',
        'is_correct'
    ];

    public function session()
    {
        return $this->belongsTo(AssessmentSession::class, 'session_id');
    }

    public function question()
    {
        return $this->belongsTo(KnowledgeCheckQuestion::class, 'question_id');
    }
}
