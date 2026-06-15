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
 * can batch them across messages. Delete and release therefore only flag the
 * job's local state for the worker loop — bypassing the SqsJob calls that
 * would hit SQS — and report the outcome (with the requested release delay)
 * back to the agent via POST /result, which the poller acts on.
 */
class CloudJob extends SqsJob
{
    /**
     * Whether the outcome has already been reported to the agent. A job may
     * reach more than one terminal call (e.g. it releases itself and then
     * throws); this guard ensures only the first outcome is POSTed.
     */
    protected bool $reported = false;

    /**
     * Create a new job instance.
     *
     * @param  array  $job
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  callable(string, int|null): void  $reporter
     */
    public function __construct(
        Container $container,
        SqsClient $sqs,
        array $job,
        $connectionName,
        $queue,
        protected $reporter,
    ) {
        parent::__construct($container, $sqs, $job, $connectionName, $queue);
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        // Flag the job deleted for queue:work without the SqsJob::delete()
        // DeleteMessage call — the poller owns the actual SQS delete. We skip
        // straight to Job::delete() so SqsJob's SQS mutation never runs.
        Job::delete();

        // SqsJob::delete() always deletes the message, and the base fail()
        // routes a terminally-failed job (already recorded in failed_jobs)
        // through delete() too — so a delete always means "remove from SQS".
        // We report "processed" in both cases to mirror that. The poller's
        // "failed" status is reserved for dispatches that never cleanly ack.
        $this->report('processed');
    }

    /**
     * Release the job back into the queue after (n) seconds.
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        // Flag the job released for queue:work without the SqsJob::release()
        // ChangeMessageVisibility call — the poller resets visibility to the
        // reported delay. We skip straight to Job::release() to avoid SQS.
        Job::release($delay);

        $this->report('released', delay: $delay);
    }

    /**
     * Report the job's terminal outcome back to the agent, at most once. The
     * release delay (in seconds) is forwarded only for the "released" status so
     * the poller can reset the message's visibility to it.
     */
    protected function report(string $status, ?int $delay = null): void
    {
        if ($this->reported) {
            return;
        }

        $this->reported = true;

        ($this->reporter)($status, $delay);
    }
}
