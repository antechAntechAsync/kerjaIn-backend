<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterestResult extends Model
{

    protected $fillable = [
        'user_id',
        'question_id',
        'option_id',
        'score'
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function question()
    {
        return $this->belongsTo(InterestQuestion::class);
    }

    public function option()
    {
        return $this->belongsTo(InterestOption::class);
    }

}
