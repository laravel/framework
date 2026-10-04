<?php

namespace Illuminate\Console\Scheduling\Sequences\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Sequences\Models\ScheduledSequence;

class ListSequencesCommand extends Command
{
    protected $signature = 'sequences:list {--status=} {--entity-type=} {--entity-id=}';

    protected $description = 'Inspect up to 100 sequences without reading the queue';

    public function handle(): void
    {
        $query = ScheduledSequence::query()->orderBy('id');
        foreach (['status', 'entity-type', 'entity-id'] as $filter) {
            if ($this->option($filter) !== null) {
                $query->where(str_replace('-', '_', $filter), $this->option($filter));
            }
        }
        $this->table(['ID', 'Definition', 'Entity', 'Revision', 'Status', 'Next (UTC)'], $query->limit(100)->get()->map(fn (ScheduledSequence $sequence) => [
            $sequence->id, $sequence->definition, $sequence->entity_type.':'.$sequence->entity_id,
            $sequence->revision, $sequence->status, $sequence->next_at?->toIso8601String(),
        ]));
    }
}
