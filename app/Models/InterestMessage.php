<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterestMessage extends Model
{
    protected $fillable = [
        'session_id',
        'sender',
        'message',
    ];

    public function session()
    {
        return $this->belongsTo(InterestSession::class);
    }
}
