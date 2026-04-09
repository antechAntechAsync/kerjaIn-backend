<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobSkillRequirement extends Model
{
    protected $table = 'job_skill_requirements';

    protected $fillable = [
        'job_listing_id',
        'name',
    ];

    public function jobListing()
    {
        return $this->belongsTo(JobListing::class, 'job_listing_id');
    }
}
