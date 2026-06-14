<?php

namespace Illuminate\Foundation\Cloud;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\SqsJob;

/**
 * An SQS job that was handed to the worker by the in-container cloud-agent over
 * its unix-socket runtime API rather than received directly from SQS.
 *
 * Delete / release / fail still go to SQS through the inherited SqsClient — the
 * agent never touches the message — but each terminal transition is also
 * reported back to the agent so it can stop heartbeating the message's
 * visibility, free itself for the next invoke, and return the outcome to the
 * dispatcher.
 */
class AgentSqsJob extends SqsJob
{
    /**
     * Whether the outcome has already been reported to the agent. The base
     * fail() routes through delete(), so without this guard a failed job would
     * report twice.
     */
    protected bool $reported = false;

    /**
     * Set while fail() is unwinding so the delete() it triggers reports the
     * outcome as "failed" rather than "processed".
     */
    protected bool $failing = false;

    /**
     * Create a new job instance.
     *
     * @param  array  $job
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  callable(string, string|null): void  $reporter
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
        parent::delete();

        $this->report($this->failing ? 'failed' : 'processed');
    }

    /**
     * Release the job back into the queue after (n) seconds.
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        $this->report('released');

        parent::release($delay);
    }

    /**
     * Delete the job, call the "failed" method, and raise the failed job event.
     *
     * @param  \Throwable|null  $e
     * @return void
     */
    public function fail($e = null)
    {
        $this->failing = true;

        parent::fail($e);
    }

    /**
     * Report the job's terminal outcome back to the agent, at most once.
     */
    protected function report(string $status, ?string $error = null): void
    {
        if ($this->reported) {
            return;
        }

        $this->reported = true;

        ($this->reporter)($status, $error);
    }
}
