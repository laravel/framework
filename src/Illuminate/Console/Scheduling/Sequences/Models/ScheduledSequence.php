<?php

namespace Illuminate\Console\Scheduling\Sequences\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ScheduledSequence extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'offsets' => 'array',
            'starts_at' => 'immutable_datetime',
            'next_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'revision' => 'integer',
            'next_number' => 'integer',
            'repeat_seconds' => 'integer',
        ];
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(ScheduledOccurrence::class, 'sequence_id');
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('next_at', '<=', now('UTC'));
    }

    public function scopeForEntity(Builder $query, Model $entity): Builder
    {
        return $query->where('entity_type', $entity->getMorphClass())->where('entity_id', (string) $entity->getKey());
    }
}
