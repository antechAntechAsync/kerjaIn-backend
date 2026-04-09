<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'file_url',
        'file_type',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
