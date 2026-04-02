<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRoadmap extends Model
{
    protected $fillable = [
        'user_id',
        'roadmap_id',
        'is_active',
        'version'
    ];

    protected $cast = [
        'is_active' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function roadmap()
    {
        return $this->belongsTo(Roadmap::class, 'roadmap_id');
    }
}
