<?php

namespace Illuminate\Tests\Integration\Console\Scheduling\Sequences;

use Illuminate\Console\Scheduling\Sequences\Models\ScheduledOccurrence;
use Illuminate\Console\Scheduling\Sequences\SequenceDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProcessSequence extends SequenceDefinition
{
    public function offsets(): array
    {
        return ['PT0S'];
    }

    public function execute(Model $entity, ScheduledOccurrence $occurrence): void
    {
        DB::table('sequence_effects')->insertOrIgnore(['identity' => $occurrence->idempotencyKey()]);
    }
}
