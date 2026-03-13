<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareerRole extends Model
{
    protected $fillable = [
        'name',
        'description',
        'subfield_id'
    ];

    public function subfield()
    {
        return $this->belongsTo(InterestSubfield::class, 'subfield_id');
    }
}
