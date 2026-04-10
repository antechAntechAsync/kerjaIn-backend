<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $table = 'skills';

    protected $fillable = [
        'name',
    ];

    public function nodes()
    {
        return $this->hasMany(RoadmapNode::class, 'skill_id');
    }

    public function userSkillStats()
    {
        return $this->hasMany(UserSkillStat::class, 'skill_id');
    }
}
