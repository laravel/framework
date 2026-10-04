<?php

namespace Illuminate\Console\Scheduling\Sequences\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ScheduledOccurrence extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
            'lease_until' => 'immutable_datetime',
            'revision' => 'integer',
            'number' => 'integer',
            'attempts' => 'integer',
        ];
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(ScheduledSequence::class, 'sequence_id');
    }

    public function idempotencyKey(): string
    {
        return "sequence:{$this->sequence_id}:revision:{$this->revision}:occurrence:{$this->number}";
    }

    public function outbox(): HasOne
    {
        return $this->hasOne(ScheduleOutbox::class, 'occurrence_id');
    }
}
