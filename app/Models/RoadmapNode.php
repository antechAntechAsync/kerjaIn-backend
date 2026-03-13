<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoadmapNode extends Model
{
    protected $fillable = [
        'roadmap_id',
        'skill_name',
        'order_index'
    ];

    public function roadmap()
    {
        return $this->belongsTo(Roadmap::class, 'roadmap_id');
    }
}
