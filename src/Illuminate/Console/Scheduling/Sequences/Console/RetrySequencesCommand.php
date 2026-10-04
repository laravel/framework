<?php

namespace Illuminate\Console\Scheduling\Sequences\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Sequences\Models\ScheduledOccurrence;
use Illuminate\Console\Scheduling\Sequences\SequenceManager;

class RetrySequencesCommand extends Command
{
    protected $signature = 'sequences:retry {occurrence}';

    protected $description = 'Retry a failed occurrence using its original idempotency key';

    public function handle(SequenceManager $manager): void
    {
        $manager->retry(ScheduledOccurrence::query()->findOrFail($this->argument('occurrence')));
        $this->info('Occurrence returned to the outbox.');
    }
}
