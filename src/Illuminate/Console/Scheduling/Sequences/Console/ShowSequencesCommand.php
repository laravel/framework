<?php

namespace Illuminate\Console\Scheduling\Sequences\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Sequences\Models\ScheduledSequence;

class ShowSequencesCommand extends Command
{
    protected $signature = 'sequences:show {sequence}';

    protected $description = 'Inspect a sequence and its latest 100 occurrences as JSON';

    public function handle(): void
    {
        $sequence = ScheduledSequence::query()->findOrFail($this->argument('sequence'));
        $sequence->load(['occurrences' => fn ($query) => $query->latest('id')->limit(100)->with('outbox')]);
        $this->line($sequence->toJson(JSON_PRETTY_PRINT));
    }
}
