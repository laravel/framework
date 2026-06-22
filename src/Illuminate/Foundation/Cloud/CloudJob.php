<?php

namespace Illuminate\Foundation\Cloud;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Queue\Jobs\SqsJob;

/**
 * An SQS job that was handed to the worker by the in-container cloud-agent over
 * its unix-socket runtime API rather than received directly from SQS.
 *
 * This pod never mutates SQS itself: the poller owns every terminal SQS op
 * (delete on success or terminal failure, visibility reset on release) so it
 * can batch them across messages. delete()/release() therefore only flag the
 * job for queue:work — via the base Job, skipping SqsJob's SQS calls entirely —
 * and report the outcome (with the requested release delay) back to the agent
 * via POST /result, which the poller acts on.
 *
 * Reporting an outcome is fire-and-trust: report() returns once the agent has
 * accepted the outcome and owns it, or it throws. An agent that rejects a
 * report — an outcome it cannot apply, such as a result for a message it has
 * already finalized or an out-of-order operation — surfaces the rejection as an
 * exception rather than CloudJob suppressing the call or papering over it; the
 * agent is the single authority on what ordering of operations is valid. An
 * unreachable agent has crashed and raises an AgentUnreachableException, which
 * the worker treats as a lost connection and exits on (matching how popping a
 * job handles an unreachable agent), restarting the pod. The job is never lost:
 * a crashed agent stops heartbeating the message's visibility, so SQS redelivers
 * it once the visibility timeout lapses, exactly as for any unacknowledged SQS
 * message.
 *
 * Because a rejected report throws, delete()'s overflow-payload purge is only
 * reached once the agent has accepted the "processed" outcome; a rejected or
 * unreachable report leaves the offloaded payload in place for the redelivery.
 */
class CloudJob extends SqsJob
{
    /**
     * Create a new job instance.
     *
     * @param  array  $job
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  callable(string, int|null): void  $reporter
     * @param  array  $overflowStorage
     */
    public function __construct(
        Container $container,
        SqsClient $sqs,
        array $job,
        $connectionName,
        $queue,
        protected $reporter,
        array $overflowStorage = [],
    ) {
        parent::__construct($container, $sqs, $job, $connectionName, $queue, $overflowStorage);
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        // Flag the job deleted for queue:work via the base Job, deliberately
        // skipping SqsJob::delete() so the SQS DeleteMessage is left to the
        // poller. The offloaded overflow payload is likewise purged only once
        // the agent has accepted the outcome (below).
        Job::delete();

        // A delete always means "remove from SQS": the base fail() routes a
        // terminally-failed job (already recorded in failed_jobs) through
        // delete() too. We report "processed" in both cases to mirror that;
        // the poller's "failed" status is reserved for dispatches that never
        // cleanly ack.
        $this->report('processed');

        // Purge the offloaded overflow payload only once the agent has accepted
        // the outcome. report() throws when the agent rejects or is unreachable,
        // so reaching here means the message is gone and the payload is safe to
        // drop; a thrown report leaves it in place for the redelivered job to
        // resolve from the cache.
        $this->deleteOverflowPayload();
    }

    /**
     * Release the job back into the queue after (n) seconds.
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        // Flag the job released for queue:work via the base Job, deliberately
        // skipping SqsJob::release() so the visibility reset is left to the
        // poller, which uses the reported delay.
        Job::release($delay);

        $this->report('released', delay: $delay);
    }

    /**
     * Report the job's outcome to the agent, which owns the SQS operation.
     *
     * The release delay (in seconds) is forwarded only for the "released" status
     * so the poller can reset the message's visibility to it. This is
     * fire-and-trust: it returns once the agent has accepted the outcome and
     * throws otherwise (see reportResultToAgent). It deliberately keeps no memory
     * of prior reports — enforcing idempotency or a valid order of operations is
     * the agent's responsibility, not something CloudJob suppresses calls to fake.
     */
    protected function report(string $status, ?int $delay = null): void
    {
        ($this->reporter)($status, $delay);
    }
}
