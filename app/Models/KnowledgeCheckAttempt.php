<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeCheckAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'roadmap_node_id',
        'score',
        'is_passed',
        'feedback',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_passed' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(RoadmapNode::class, 'roadmap_node_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(KnowledgeCheckQuestion::class);
    }
}
