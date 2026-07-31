<?php

namespace Illuminate\Foundation\Cloud;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Queue\Jobs\SqsJob;

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
        // Skip SQS deletion so SQS DeleteMessage is left to the poller...
        Job::delete();

        $this->report('processed');

        // Only reached once the agent has accepted the outcome (report() throws otherwise)...
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
        // Skip SQS deletion so SQS release is left to the poller...
        Job::release($delay);

        $this->report('released', delay: $delay);
    }

    /**
     * Report the job's outcome to the agent, which owns the SQS operation.
     */
    protected function report(string $status, ?int $delay = null): void
    {
        ($this->reporter)($status, $delay);
    }
}
