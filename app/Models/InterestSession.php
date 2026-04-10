<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterestSession extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'result_role',
    ];

    public function messages()
    {
        return $this->hasMany(InterestMessage::class, 'session_id');
    }
}
