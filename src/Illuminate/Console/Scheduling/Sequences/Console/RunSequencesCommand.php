<?php

namespace Illuminate\Console\Scheduling\Sequences\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Sequences\DueSequenceRunner;
use Illuminate\Console\Scheduling\Sequences\OutboxPublisher;

class RunSequencesCommand extends Command
{
    protected $signature = 'sequences:run {--limit=100}';

    protected $description = 'Durably schedule a bounded batch of due occurrences';

    public function handle(DueSequenceRunner $runner, OutboxPublisher $publisher): void
    {
        $this->info('Occurrences scheduled: '.$runner->run((int) $this->option('limit')));
        $this->info('Occurrences published: '.$publisher->publish((int) $this->option('limit')));
    }
}
