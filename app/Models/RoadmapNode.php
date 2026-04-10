<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoadmapNode extends Model
{
    protected $fillable = [
        'roadmap_id',
        'skill_name',
        'skill_id',
        'order_index',
        'description',
    ];

    public function roadmap()
    {
        return $this->belongsTo(Roadmap::class, 'roadmap_id');
    }

    public function progresses()
    {
        return $this->hasMany(UserProgress::class, 'roadmap_node_id');
    }

    public function assesmentAnswers()
    {
        return $this->hasMany(AssessmentAnswer::class, 'roadmap_node_id');
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }

    public function resources()
    {
        return $this->hasMany(RoadmapNodeResource::class, 'roadmap_node_id');
    }

    public function getSkillNameAttribute()
    {
        return $this->skill?->name;
    }
}
