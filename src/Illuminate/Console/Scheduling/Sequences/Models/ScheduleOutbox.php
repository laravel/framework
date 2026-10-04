<?php

namespace Illuminate\Console\Scheduling\Sequences\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleOutbox extends Model
{
    protected $table = 'schedule_outbox';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'available_at' => 'immutable_datetime',
            'lease_until' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'attempts' => 'integer',
        ];
    }

    public function occurrence(): BelongsTo
    {
        return $this->belongsTo(ScheduledOccurrence::class, 'occurrence_id');
    }
}
