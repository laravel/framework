<?php

namespace Illuminate\Foundation\Cloud;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\SqsJob;

/**
 * An SQS job that was handed to the worker by the in-container cloud-agent over
 * its unix-socket runtime API rather than received directly from SQS.
 *
 * This pod never mutates SQS itself: the poller owns every terminal SQS op
 * (delete on success or terminal failure, visibility reset on release) so it
 * can batch them across messages. We therefore override only SqsJob's two
 * SQS-touching seams (delete/change-visibility) to no-ops — inheriting all of
 * its other bookkeeping, such as overflow-payload cache cleanup — and report
 * the outcome (with the requested release delay) back to the agent via
 * POST /result, which the poller acts on.
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
        // Runs Job::delete() (flagging the job for queue:work) and SqsJob's
        // non-SQS bookkeeping (overflow cache cleanup), while our overridden
        // deleteMessageFromSqs() no-op leaves the actual SQS delete to the
        // poller.
        parent::delete();

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
        // Runs Job::release() (flagging the job for queue:work) while our
        // overridden changeMessageVisibilityInSqs() no-op leaves the visibility
        // reset to the poller, which uses the reported delay.
        parent::release($delay);

        $this->report('released', delay: $delay);
    }

    /**
     * The poller owns the SQS DeleteMessage so it can batch deletes.
     *
     * @return void
     */
    protected function deleteMessageFromSqs()
    {
        //
    }

    /**
     * The poller owns the SQS ChangeMessageVisibility for releases.
     *
     * @param  int  $delay
     * @return void
     */
    protected function changeMessageVisibilityInSqs($delay)
    {
        //
    }

    /**
     * Ensure an outcome has been reported to the agent.
     *
     * Used when the worker is torn down mid-job (timeout, fatal error) and the
     * normal delete()/release() reporting never ran. A no-op once an outcome
     * has already been reported.
     */
    public function reportToAgent(string $status, ?int $delay = null): void
    {
        $this->report($status, $delay);
    }

    /**
     * Report the job's outcome back to the agent.
     *
     * Each distinct outcome is reported once, and a terminal "processed"
     * supersedes a prior "released". The release delay (in seconds) is
     * forwarded only for the "released" status so the poller can reset the
     * message's visibility to it.
     */
    protected function report(string $status, ?int $delay = null): void
    {
        if ($this->reportedStatus === $status || $this->reportedStatus === 'processed') {
            return;
        }

        ($this->reporter)($status, $delay);

        $this->reportedStatus = $status;
    }
}
