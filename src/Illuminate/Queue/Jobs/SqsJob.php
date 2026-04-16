<?php

namespace Illuminate\Queue\Jobs;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Support\Arr;

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
     * The extended store options for large payload offloading.
     *
     * @var array
     */
    protected $extendedStoreOptions = [];

    /**
     * The cached raw body of the job.
     *
     * @var string|null
     */
    protected $cachedRawBody = null;

    /**
     * Create a new job instance.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Aws\Sqs\SqsClient  $sqs
     * @param  array  $job
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  array  $extendedStoreOptions
     */
    public function __construct(Container $container, SqsClient $sqs, array $job, $connectionName, $queue, array $extendedStoreOptions = [])
    {
        $this->sqs = $sqs;
        $this->job = $job;
        $this->queue = $queue;
        $this->container = $container;
        $this->connectionName = $connectionName;
        $this->extendedStoreOptions = $extendedStoreOptions;
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

        $this->sqs->deleteMessage([
            'QueueUrl' => $this->queue, 'ReceiptHandle' => $this->job['ReceiptHandle'],
        ]);

        if (Arr::get($this->extendedStoreOptions, 'cleanup') && $pointer = $this->resolvePointer()) {
            $this->resolveDisk()->delete($pointer);
        }
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
        if ($this->cachedRawBody !== null) {
            return $this->cachedRawBody;
        }

        if ($pointer = $this->resolvePointer()) {
            return $this->cachedRawBody = $this->resolveDisk()->get($pointer);
        }

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

    /**
     * Resolve the pointer path from the job body, if present.
     *
     * @return string|null
     */
    protected function resolvePointer()
    {
        $body = $this->job['Body'] ?? null;

        if (! is_string($body) || $body === '') {
            return null;
        }

        $decoded = json_decode($body, true);

        if (! is_array($decoded) || ! isset($decoded['@pointer'])) {
            return null;
        }

        return is_string($decoded['@pointer']) ? $decoded['@pointer'] : null;
    }

    /**
     * Resolve the configured filesystem disk for extended storage.
     *
     * @return \Illuminate\Filesystem\FilesystemAdapter
     */
    protected function resolveDisk()
    {
        return $this->container->make('filesystem')->disk(
            Arr::get($this->extendedStoreOptions, 'disk')
        );
    }
}
