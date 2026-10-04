<?php

namespace Illuminate\Console\Scheduling\Sequences\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Sequences\OutboxPublisher;

class PublishSequencesCommand extends Command
{
    protected $signature = 'sequences:publish {--limit=100}';

    protected $description = 'Publish recoverable outbox entries to the queue';

    public function handle(OutboxPublisher $publisher): void
    {
        $this->info('Occurrences published: '.$publisher->publish((int) $this->option('limit')));
    }
}
