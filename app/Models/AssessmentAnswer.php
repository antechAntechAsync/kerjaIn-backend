<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentAnswer extends Model
{
    protected $fillable = [
        'session_id',
        'roadmap_node_id',
        'scale_value',
    ];

    public function roadmapNode()
    {
        return $this->belongsTo(RoadmapNode::class, 'roadmap_node_id');
    }
}
