<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobListing extends Model
{
    protected $table = 'job_listings';

    protected $fillable = [
        'professional_id',
        'title',
        'description',
        'location',
        'level',
    ];

    public function professional()
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function requirements()
    {
        return $this->hasMany(JobSkillRequirement::class, 'job_listing_id');
    }
}
