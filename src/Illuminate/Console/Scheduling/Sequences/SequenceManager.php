<?php

namespace Illuminate\Console\Scheduling\Sequences;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Console\Scheduling\Sequences\Models\ScheduledOccurrence;
use Illuminate\Console\Scheduling\Sequences\Models\ScheduledSequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SequenceManager
{
    public function __construct(private DefinitionRegistry $definitions, private ScheduleClock $clock)
    {
    }

    public function start(string $definition, Model $entity, DateTimeInterface $start, string $timezone = 'UTC'): ScheduledSequence
    {
        if (! $entity->exists || $entity->getKey() === null) {
            throw new InvalidArgumentException('The sequence entity must already be persisted.');
        }

        $implementation = $this->definitions->resolve($definition);
        $start = CarbonImmutable::instance($start)->utc()->startOfSecond();
        $offsets = $implementation->offsets();
        $repeat = $implementation->repeatEverySeconds();
        $this->clock->validate($offsets, $repeat, $start, $timezone);

        return ScheduledSequence::query()->create([
            'definition' => $definition,
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => (string) $entity->getKey(),
            'revision' => 1,
            'status' => 'active',
            'starts_at' => $start,
            'timezone' => $timezone,
            'offsets' => $offsets,
            'repeat_seconds' => $repeat,
            'next_number' => 0,
            'next_at' => $this->clock->offset($start, $timezone, $offsets[0]),
        ]);
    }

    public function cancel(ScheduledSequence $sequence): ScheduledSequence
    {
        return DB::transaction(function () use ($sequence): ScheduledSequence {
            $current = ScheduledSequence::query()->lockForUpdate()->findOrFail($sequence->id);

            if ($current->status !== 'cancelled') {
                $current->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now('UTC'),
                    'next_at' => null,
                    'revision' => $current->revision + 1,
                ]);
            }

            return $current;
        }, 3);
    }

    public function reschedule(ScheduledSequence $sequence, DateTimeInterface $start): ScheduledSequence
    {
        return DB::transaction(function () use ($sequence, $start): ScheduledSequence {
            $current = ScheduledSequence::query()->lockForUpdate()->findOrFail($sequence->id);
            $start = CarbonImmutable::instance($start)->utc()->startOfSecond();
            $this->clock->validate($current->offsets, $current->repeat_seconds, $start, $current->timezone);
            $current->update([
                'starts_at' => $start,
                'revision' => $current->revision + 1,
                'next_number' => 0,
                'next_at' => $this->clock->offset($start, $current->timezone, $current->offsets[0]),
                'status' => 'active',
                'cancelled_at' => null,
            ]);

            return $current;
        }, 3);
    }

    public function retry(ScheduledOccurrence $occurrence): void
    {
        DB::transaction(function () use ($occurrence): void {
            $sequence = ScheduledSequence::query()->lockForUpdate()->findOrFail($occurrence->sequence_id);
            $current = ScheduledOccurrence::query()->lockForUpdate()->findOrFail($occurrence->id);
            if ($current->status !== 'failed' || $sequence->status === 'cancelled' || $current->revision !== $sequence->revision) {
                throw new InvalidArgumentException('Only failed occurrences in the current, uncancelled revision can be retried.');
            }
            $current->update(['status' => 'pending', 'attempts' => 0, 'finished_at' => null, 'last_error' => null, 'claim_token' => null, 'lease_until' => null]);
            $current->outbox()->update(['published_at' => null, 'available_at' => now('UTC'), 'lease_until' => null, 'claim_token' => null, 'last_error' => null]);
        }, 3);
    }
}
