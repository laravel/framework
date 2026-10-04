<?php

namespace Illuminate\Console\Scheduling\Sequences;

use Illuminate\Console\Scheduling\Sequences\Models\ScheduledOccurrence;
use Illuminate\Console\Scheduling\Sequences\Models\ScheduledSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class OccurrenceExecutor
{
    public function __construct(private DefinitionRegistry $definitions)
    {
    }

    /** Returns false when another worker holds a live lease. */
    public function execute(int $id): bool
    {
        $token = (string) Str::uuid();
        $claim = DB::transaction(function () use ($id, $token): ScheduledOccurrence|bool {
            $snapshot = ScheduledOccurrence::query()->find($id);
            if ($snapshot === null) {
                return true;
            }

            $sequence = ScheduledSequence::query()->lockForUpdate()->findOrFail($snapshot->sequence_id);
            $occurrence = ScheduledOccurrence::query()->lockForUpdate()->findOrFail($id);
            if (in_array($occurrence->status, ['succeeded', 'skipped', 'failed'], true)) {
                return true;
            }
            if ($occurrence->lease_until?->isFuture()) {
                return false;
            }
            if ($sequence->status === 'cancelled' || $sequence->revision !== $occurrence->revision) {
                $occurrence->update(['status' => 'skipped', 'finished_at' => now('UTC'), 'claim_token' => null, 'lease_until' => null]);

                return true;
            }
            if ($occurrence->attempts >= config('scheduling.max_attempts', 3)) {
                $occurrence->update(['status' => 'failed', 'finished_at' => now('UTC'), 'claim_token' => null, 'lease_until' => null]);

                return true;
            }
            $occurrence->update([
                'status' => 'running',
                'attempts' => $occurrence->attempts + 1,
                'claim_token' => $token,
                'lease_until' => now('UTC')->addSeconds(60),
                'started_at' => now('UTC'),
            ]);

            return $occurrence;
        }, 3);

        if (is_bool($claim)) {
            return $claim;
        }

        try {
            $sequence = $claim->sequence;
            $definition = $this->definitions->resolve($sequence->definition);
            $entity = $sequence->entity;
            if ($sequence->status === 'cancelled' || $sequence->revision !== $claim->revision || $entity === null || ! $definition->shouldContinue($entity)) {
                $this->finish($id, $token, ['status' => 'skipped', 'finished_at' => now('UTC')]);

                return true;
            }

            $definition->execute($entity, $claim);
            $this->finish($id, $token, ['status' => 'succeeded', 'finished_at' => now('UTC'), 'last_error' => null]);
        } catch (Throwable $exception) {
            $terminal = $claim->attempts >= config('scheduling.max_attempts', 3);
            $this->finish($id, $token, [
                'status' => $terminal ? 'failed' : 'pending',
                'finished_at' => $terminal ? now('UTC') : null,
                'last_error' => mb_substr($exception->getMessage(), 0, 2000),
            ]);
            if (! $terminal) {
                throw $exception;
            }
            report($exception);
        }

        return true;
    }

    /** @param array<string, mixed> $values */
    private function finish(int $id, string $token, array $values): void
    {
        ScheduledOccurrence::query()->whereKey($id)->where('claim_token', $token)->update([
            ...$values,
            'claim_token' => null,
            'lease_until' => null,
        ]);
    }
}
