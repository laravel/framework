<?php

namespace Illuminate\Console\Scheduling\Sequences;

use Illuminate\Console\Scheduling\Sequences\Jobs\ExecuteScheduledOccurrence;
use Illuminate\Console\Scheduling\Sequences\Models\ScheduleOutbox;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;
use Throwable;

class OutboxPublisher
{
    public function __construct(private Factory $queues)
    {
    }

    public function publish(int $limit = 100): int
    {
        if (DB::transactionLevel() !== 0) {
            throw new LogicException('Publish the outbox outside application transactions, after scheduling state has committed.');
        }

        $count = 0;
        $ids = $this->available()->orderBy('id')->limit(max(0, $limit))->pluck('id');
        foreach ($ids as $id) {
            $token = (string) Str::uuid();
            $claimed = $this->available()->whereKey($id)->update([
                'claim_token' => $token,
                'lease_until' => now('UTC')->addSeconds(60),
            ]);
            if ($claimed === 0) {
                continue;
            }

            $entry = ScheduleOutbox::query()->findOrFail($id);
            $entry->increment('attempts');
            try {
                $driver = config("queue.connections.{$entry->connection}.driver");
                if (! in_array($driver, ['database', 'redis', 'sqs', 'beanstalkd'], true)) {
                    throw new RuntimeException('Scheduled actions require a durable database, Redis, SQS, or Beanstalkd queue.');
                }
                $this->queues->connection($entry->connection)->push(
                    (new ExecuteScheduledOccurrence($entry->occurrence_id))->beforeCommit(),
                    '',
                    $entry->queue,
                );
                ScheduleOutbox::query()->whereKey($id)->where('claim_token', $token)->update([
                    'published_at' => now('UTC'),
                    'claim_token' => null,
                    'lease_until' => null,
                    'last_error' => null,
                ]);
                $count++;
            } catch (Throwable $exception) {
                ScheduleOutbox::query()->whereKey($id)->where('claim_token', $token)->update([
                    'claim_token' => null,
                    'lease_until' => null,
                    'available_at' => now('UTC')->addSeconds(min(300, 5 * $entry->attempts)),
                    'last_error' => mb_substr($exception->getMessage(), 0, 2000),
                ]);
                report($exception);
            }
        }

        return $count;
    }

    private function available(): Builder
    {
        return ScheduleOutbox::query()->whereNull('published_at')
            ->where('available_at', '<=', now('UTC'))
            ->where(fn (Builder $query) => $query->whereNull('lease_until')->orWhere('lease_until', '<=', now('UTC')));
    }
}
