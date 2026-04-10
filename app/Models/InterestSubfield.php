<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterestSubfield extends Model
{
    protected $fillable = [
        'field_id',
        'name',
    ];

    public function field()
    {
        return $this->belongsTo(InterestField::class, 'field_id');
    }

    public function questions()
    {
        return $this->hasMany(InterestQuestion::class, 'subfield_id');
    }
}
