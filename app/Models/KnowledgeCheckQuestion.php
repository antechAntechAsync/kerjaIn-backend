<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeCheckQuestion extends Model
{
    protected $fillable = [
        'knowledge_check_attempt_id',
        'roadmap_node_id',
        'question',
        'topic',
        'options',
        'correct_answer',
        'difficulty',
        'weight',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function node()
    {
        return $this->belongsTo(RoadmapNode::class, 'roadmap_node_id');
    }

    public function attempt()
    {
        return $this->belongsTo(KnowledgeCheckAttempt::class, 'knowledge_check_attempt_id');
    }
}
