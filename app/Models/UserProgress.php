<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProgress extends Model
{
    protected $fillable = [
        'user_id',
        'roadmap_node_id',
        'status',
        'completed_at',
    ];

    public function node()
    {
        return $this->belongsTo(RoadmapNode::class, 'roadmap_node_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
