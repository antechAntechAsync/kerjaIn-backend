<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $table = 'job_applications';

    protected $fillable = [
        'user_id',
        'job_id',
        'project_id',
        'cover_letter',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function job()
    {
        return $this->belongsTo(JobListing::class, 'job_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
