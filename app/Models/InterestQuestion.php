<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterestQuestion extends Model
{
    protected $fillable = [
        'subfield_id',
        'question',
        'weight',
    ];

    public function subfield()
    {
        return $this->belongsTo(InterestSubfield::class);
    }

    public function options()
    {
        return $this->hasMany(InterestOption::class, 'question_id');
    }
}
