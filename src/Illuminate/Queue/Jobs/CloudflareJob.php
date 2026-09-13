<?php

namespace Illuminate\Queue\Jobs;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\CloudflareQueueClient;

class CloudflareJob extends Job implements JobContract
{
    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $message
     */
    public function __construct(
        Container $container,
        protected CloudflareQueueClient $client,
        protected array $message,
        string $connectionName,
        protected string $queueName,
    ) {
        $this->container = $container;
        $this->connectionName = $connectionName;
        $this->queue = $queueName;
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();

        $this->client->ack([$this->message['lease_id']]);
    }

    /**
     * Release the job back onto the queue after (n) seconds.
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);

        $this->client->retry([
            [
                'lease_id' => $this->message['lease_id'],
                'delay_seconds' => max(0, (int) $delay),
            ],
        ]);
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return (int) ($this->message['attempts'] ?? 1);
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->message['id'];
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->message['body'];
    }

    /**
     * Get the lease ID for this message.
     *
     * @return string
     */
    public function getLeaseId()
    {
        return $this->message['lease_id'];
    }

    /**
     * Get the underlying raw Cloudflare message.
     *
     * @return array<string, mixed>
     */
    public function getMessage()
    {
        return $this->message;
    }
}
