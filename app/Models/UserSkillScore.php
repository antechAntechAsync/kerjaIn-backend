<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSkillScore extends Model
{
    protected $fillable = [
        'user_id',
        'skill_name',
        'score',
        'source',
        'roadmap_node_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roadmapNode(): BelongsTo
    {
        return $this->belongsTo(RoadmapNode::class);
    }
}
