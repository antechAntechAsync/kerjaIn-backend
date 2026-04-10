<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeCheckQuestion extends Model
{
    protected $fillable = [
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
}
