<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentScale extends Model
{
    protected $fillable = [
        'value',
        'label',
        'description',
    ];
}
