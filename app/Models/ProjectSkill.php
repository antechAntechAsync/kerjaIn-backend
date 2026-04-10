<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'roadmap_node_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function node()
    {
        return $this->belongsTo(RoadmapNode::class, 'roadmap_node_id');
    }
}
