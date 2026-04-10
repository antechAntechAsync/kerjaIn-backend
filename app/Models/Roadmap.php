<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roadmap extends Model
{
    protected $fillable = [
        'career_role',
        'level',
    ];

    public function nodes()
    {
        return $this->hasMany(RoadmapNode::class);
    }

    public function users()
    {
        return $this->hasMany(UserRoadmap::class);
    }
}
