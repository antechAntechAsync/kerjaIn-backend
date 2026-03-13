<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterestField extends Model
{

    protected $fillable = [
        'name'
    ];

    public function subfields()
    {
        return $this->hasMany(InterestSubfield::class, 'field_id');
    }

}
