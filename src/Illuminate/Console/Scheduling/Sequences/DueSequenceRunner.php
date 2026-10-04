<?php

namespace Illuminate\Console\Scheduling\Sequences;

use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Sequences\Models\ScheduledOccurrence;
use Illuminate\Console\Scheduling\Sequences\Models\ScheduledSequence;
use Illuminate\Console\Scheduling\Sequences\Models\ScheduleOutbox;
use Illuminate\Support\Facades\DB;
use Throwable;

class DueSequenceRunner
{
    public function __construct(private DefinitionRegistry $definitions, private ScheduleClock $clock)
    {
    }

    public function run(int $limit = 100): int
    {
        $created = 0;
        $failed = [];
        for ($i = 0; $i < max(0, $limit); $i++) {
            $id = ScheduledSequence::query()->due()->whereNotIn('id', $failed)->orderBy('next_at')->orderBy('id')->value('id');
            if ($id === null) {
                break;
            }

            try {
                $created += $this->materialize((int) $id) ? 1 : 0;
            } catch (Throwable $exception) {
                $failed[] = $id;
                ScheduledSequence::query()->whereKey($id)->where('status', 'active')->update([
                    'last_error' => mb_substr($exception->getMessage(), 0, 2000),
                ]);
                report($exception);
            }
        }

        return $created;
    }

    public function materialize(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $sequence = ScheduledSequence::query()->lockForUpdate()->find($id);
            $now = CarbonImmutable::now('UTC')->startOfSecond();
            if ($sequence === null || $sequence->status !== 'active' || $sequence->next_at === null || $sequence->next_at->gt($now)) {
                return false;
            }

            $definition = $this->definitions->resolve($sequence->definition);
            $entity = $sequence->entity;
            if ($entity === null || ! $definition->shouldContinue($entity)) {
                $sequence->update(['status' => 'cancelled', 'cancelled_at' => $now, 'next_at' => null, 'revision' => $sequence->revision + 1]);

                return false;
            }

            $occurrence = ScheduledOccurrence::query()->create([
                'sequence_id' => $sequence->id,
                'revision' => $sequence->revision,
                'number' => $sequence->next_number,
                'scheduled_at' => $sequence->next_at,
                'status' => 'pending',
            ]);
            ScheduleOutbox::query()->create([
                'occurrence_id' => $occurrence->id,
                'available_at' => $now,
                'connection' => config('scheduling.connection'),
                'queue' => config('scheduling.queue'),
            ]);
            $next = $this->clock->next($sequence, $now);
            $sequence->update([
                'next_number' => $next['number'],
                'next_at' => $next['at'],
                'status' => $next['at'] === null ? 'completed' : 'active',
                'last_error' => null,
            ]);

            return true;
        }, 3);
    }
}
