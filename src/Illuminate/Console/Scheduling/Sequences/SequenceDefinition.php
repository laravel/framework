<?php

namespace Illuminate\Console\Scheduling\Sequences;

use Illuminate\Console\Scheduling\Sequences\Models\ScheduledOccurrence;
use Illuminate\Database\Eloquent\Model;

abstract class SequenceDefinition
{
    /** @return list<string> ISO-8601 durations relative to the entity's start. */
    abstract public function offsets(): array;

    public function repeatEverySeconds(): ?int
    {
        return null;
    }

    public function shouldContinue(Model $entity): bool
    {
        return true;
    }

    /** Perform the action here; forward $occurrence->idempotencyKey() to external services. */
    abstract public function execute(Model $entity, ScheduledOccurrence $occurrence): void;
}
