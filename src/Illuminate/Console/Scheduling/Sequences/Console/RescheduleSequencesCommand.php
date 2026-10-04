<?php

namespace Illuminate\Console\Scheduling\Sequences\Console;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Sequences\Models\ScheduledSequence;
use Illuminate\Console\Scheduling\Sequences\SequenceManager;

class RescheduleSequencesCommand extends Command
{
    protected $signature = 'sequences:reschedule {sequence} {start}';

    protected $description = 'Restart a sequence from a new start time and invalidate old work';

    public function handle(SequenceManager $manager): void
    {
        $sequence = $manager->reschedule(ScheduledSequence::query()->findOrFail($this->argument('sequence')), CarbonImmutable::parse($this->argument('start'), 'UTC'));
        $this->info('Schedule restarted at revision '.$sequence->revision);
    }
}
