<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSkillStat extends Model
{
    protected $table = 'user_skill_stats';

    protected $fillable = [
        'user_id',
        'skill_id',
        'score',
        'level',
        'last_updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class, 'skill_id');
    }
}
