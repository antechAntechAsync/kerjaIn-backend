<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roadmap extends Model
{
    protected $fillable = [
        'career_role'
    ];

    public function nodes()
    {
        return $this->hasMany(RoadmapNode::class, 'roadmap_id');
    }
}
