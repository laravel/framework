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
 * An agent that responds but rejects a report has already finalized the job
 * itself (a deadline, death detection), so the message is redelivering anyway
 * and there is nothing for us to do. An agent that is unreachable has crashed:
 * the AgentUnreachableException propagates, the worker treats it as a lost
 * connection and exits — matching how popping a job handles an unreachable
 * agent — and the pod restarts. The job is never lost either way: a crashed
 * agent stops heartbeating the message's visibility, so SQS redelivers it once
 * the visibility timeout lapses, exactly as for any unacknowledged SQS message.
 *
 * Overflow-payload cache cleanup is deferred until the agent accepts a
 * "processed" report; while a report is merely rejected the message may still
 * be redelivered, so the offloaded payload is kept until then.
 */
class CloudJob extends SqsJob
{
    /**
     * The agent status already reported for this job, if any.
     *
     * A "processed" (delete) outcome is terminal and supersedes a prior
     * "released" — e.g. a job that releases itself and then fails — so the
     * agent always ends on the job's final outcome rather than the first one.
     */
    protected ?string $reportedStatus = null;

    /**
     * Create a new job instance.
     *
     * @param  array  $job
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  callable(string, int|null): bool  $reporter
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
        //
        // Only purge the offloaded overflow payload once the agent has accepted
        // the report: a rejected report means the message may be redelivered,
        // and the redelivered job must still resolve its payload from the cache.
        if ($this->report('processed')) {
            $this->deleteOverflowPayload();
        }
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
     * Report the job's outcome back to the agent.
     *
     * Each distinct outcome is reported once, and a terminal "processed"
     * supersedes a prior "released". The release delay (in seconds) is
     * forwarded only for the "released" status so the poller can reset the
     * message's visibility to it.
     *
     * Returns whether the agent now holds the outcome: false when the agent is
     * alive but rejected the report, in which case it has already finalized the
     * job itself (a deadline, death detection) and the message is redelivering
     * regardless, so the outcome is left unrecorded rather than treated as
     * delivered. An unreachable agent instead throws AgentUnreachableException
     * out of the reporter; the worker treats it as a lost connection and exits,
     * and the message redelivers when its visibility timeout lapses.
     */
    protected function report(string $status, ?int $delay = null): bool
    {
        if ($this->reportedStatus === $status || $this->reportedStatus === 'processed') {
            return true;
        }

        if (! ($this->reporter)($status, $delay)) {
            return false;
        }

        $this->reportedStatus = $status;

        return true;
    }
}
