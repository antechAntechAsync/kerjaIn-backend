<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentSession extends Model
{
    protected $fillable = [
        'user_id',
        'roadmap_id',
        'type',
    ];
}
