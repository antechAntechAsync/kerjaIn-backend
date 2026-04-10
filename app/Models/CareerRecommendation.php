<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareerRecommendation extends Model
{
    protected $fillable = [
        'user_id',
        'career_role',
        'roadmap',
    ];

    protected $casts = [
        'roadmap' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
