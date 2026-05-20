<?php

namespace Illuminate\Queue\Jobs;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;

class SqsJob extends Job implements JobContract
{
    /**
     * The Amazon SQS client instance.
     *
     * @var \Aws\Sqs\SqsClient
     */
    protected $sqs;

    /**
     * The Amazon SQS job instance.
     *
     * @var array
     */
    protected $job;

    /**
     * The redrive policy from the queue, if configured.
     *
     * @var array|null
     */
    protected $redrivePolicy;

    /**
     * Create a new job instance.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Aws\Sqs\SqsClient  $sqs
     * @param  array  $job
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  array|null  $redrivePolicy
     */
    public function __construct(Container $container, SqsClient $sqs, array $job, $connectionName, $queue, $redrivePolicy = null)
    {
        $this->sqs = $sqs;
        $this->job = $job;
        $this->queue = $queue;
        $this->container = $container;
        $this->connectionName = $connectionName;
        $this->redrivePolicy = $redrivePolicy;
    }

    /**
     * Release the job back into the queue after (n) seconds.
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);

        $this->sqs->changeMessageVisibility([
            'QueueUrl' => $this->queue,
            'ReceiptHandle' => $this->job['ReceiptHandle'],
            'VisibilityTimeout' => $delay,
        ]);
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();

        if ($this->shouldDeleteFromSqs()) {
            $this->sqs->deleteMessage([
                'QueueUrl' => $this->queue, 'ReceiptHandle' => $this->job['ReceiptHandle'],
            ]);
        }
    }

    /**
     * Determine if the message should be deleted from SQS.
     *
     * When the job has failed and the queue has a redrive policy configured,
     * we should not delete the message so that SQS can handle moving it to
     * the dead letter queue after the maxReceiveCount has been exceeded.
     *
     * @return bool
     */
    protected function shouldDeleteFromSqs()
    {
        if ($this->hasFailed() && ! is_null($this->redrivePolicy)) {
            return false;
        }

        return true;
    }

    /**
     * Get the number of times to attempt a job.
     *
     * When the queue has a redrive policy, the maxReceiveCount is used so
     * that Laravel's retry mechanism is aligned with SQS's native dead
     * letter queue routing.
     *
     * @return int|null
     */
    public function maxTries()
    {
        if (! is_null($this->redrivePolicy)) {
            return (int) $this->redrivePolicy['maxReceiveCount'];
        }

        return parent::maxTries();
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return (int) $this->job['Attributes']['ApproximateReceiveCount'];
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->job['MessageId'];
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->job['Body'];
    }

    /**
     * Get the underlying SQS client instance.
     *
     * @return \Aws\Sqs\SqsClient
     */
    public function getSqs()
    {
        return $this->sqs;
    }

    /**
     * Get the underlying raw SQS job.
     *
     * @return array
     */
    public function getSqsJob()
    {
        return $this->job;
    }
}
