<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterestOption extends Model
{

    protected $fillable = [
        'question_id',
        'option_text',
        'score'
    ];

    public function question()
    {
        return $this->belongsTo(InterestQuestion::class);
    }

}
