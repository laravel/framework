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
 * can batch them across messages. We therefore override SqsJob's two
 * SQS-touching seams (delete/change-visibility) to no-ops and report the
 * outcome (with the requested release delay) back to the agent via POST
 * /result, which the poller acts on.
 *
 * Overflow-payload cache cleanup is deferred too: because the real SQS delete
 * is owned by the poller and only happens once the agent accepts a "processed"
 * report, we must keep the offloaded payload until then — a failed report can
 * mean the message is redelivered, and the redelivered job must still resolve
 * its payload from the cache.
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
        // Runs Job::delete() (flagging the job for queue:work), while our
        // overridden deleteMessageFromSqs() and deleteOverflowPayload() no-ops
        // leave the actual SQS delete and the overflow cache cleanup to run
        // only after the agent has accepted the outcome (below).
        parent::delete();

        // SqsJob::delete() always deletes the message, and the base fail()
        // routes a terminally-failed job (already recorded in failed_jobs)
        // through delete() too — so a delete always means "remove from SQS".
        // We report "processed" in both cases to mirror that. The poller's
        // "failed" status is reserved for dispatches that never cleanly ack.
        //
        // Only purge the offloaded overflow payload once the agent has accepted
        // the report: a failed report means the message may be redelivered, and
        // the redelivered job must still resolve its payload from the cache.
        if ($this->report('processed')) {
            parent::deleteOverflowPayload();
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
     * The overflow payload is purged only after the agent accepts the outcome.
     *
     * delete() calls parent::deleteOverflowPayload() once the "processed"
     * report has been accepted; suppressing the eager cleanup here keeps the
     * payload resolvable if that report fails and the message is redelivered.
     *
     * @return void
     */
    protected function deleteOverflowPayload()
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
     *
     * Returns whether the agent now holds the outcome. The status is only
     * remembered once the reporter confirms delivery, so a report that failed
     * to reach the agent is retried by the finishProcessingJob() safety net
     * rather than being silently treated as delivered.
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
