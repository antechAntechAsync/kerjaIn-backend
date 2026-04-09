<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoadmapNodeResource extends Model
{
    protected $table = 'roadmap_node_resources';

    protected $fillable = [
        'roadmap_node_id',
        'title',
        'url',
        'type',
    ];

    public function node()
    {
        return $this->belongsTo(RoadmapNode::class, 'roadmap_node_id');
    }
}
