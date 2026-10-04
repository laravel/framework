<?php

namespace Illuminate\Console\Scheduling\Sequences\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Sequences\Models\ScheduledSequence;
use Illuminate\Console\Scheduling\Sequences\SequenceManager;

class CancelSequencesCommand extends Command
{
    protected $signature = 'sequences:cancel {sequence}';

    protected $description = 'Cancel future work and invalidate stale queued occurrences';

    public function handle(SequenceManager $manager): void
    {
        $manager->cancel(ScheduledSequence::query()->findOrFail($this->argument('sequence')));
        $this->info('Sequence cancelled. Work that already passed its execution guard may finish.');
    }
}
