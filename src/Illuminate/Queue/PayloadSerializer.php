<?php

namespace Illuminate\Queue;

use DateTimeInterface;
use Illuminate\Contracts\Queue\PayloadSerializer as PayloadSerializerContract;

class PayloadSerializer implements PayloadSerializerContract
{
    /**
     * @param array $payloadArray
     * @return string
     */
    public function serialize($payloadArray)
    {
        $payload = json_encode($payloadArray);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidPayloadException(
                'Unable to JSON encode payload. Error code: ' . json_last_error()
            );
        }

        return $payload;
    }

    /**
     * @param string $raw
     * @param string $connectionName
     * @param string $queueName
     * @return array
     */
    public function unserialize($raw, $connectionName, $queueName)
    {
        return json_decode($raw, true);
    }

    /**
     * Create a payload array from the given job and data.
     *
     * @param  string $connectionName
     * @param  string $queueName
     * @param  string $job
     * @param  mixed  $data
     * @return array
     */
    public function createPayloadArray($connectionName, $queueName, $job, $data = '')
    {
        return is_object($job)
            ? $this->createObjectPayload($job)
            : $this->createStringPayload($job, $data);
    }

    /**
     * Create a payload for an object-based queue handler.
     *
     * @param  mixed $job
     * @return array
     */
    protected function createObjectPayload($job)
    {
        return [
            'displayName' => $this->getDisplayName($job),
            'job'         => 'Illuminate\Queue\CallQueuedHandler@call',
            'maxTries'    => $job->tries ?? null,
            'timeout'     => $job->timeout ?? null,
            'timeoutAt'   => $this->getJobExpiration($job),
            'data'        => [
                'commandName' => get_class($job),
                'command'     => serialize(clone $job),
            ],
        ];
    }

    /**
     * Get the display name for the given job.
     *
     * @param  mixed $job
     * @return string
     */
    protected function getDisplayName($job)
    {
        return method_exists($job, 'displayName')
            ? $job->displayName() : get_class($job);
    }

    /**
     * Get the expiration timestamp for an object-based queue handler.
     *
     * @param  mixed $job
     * @return mixed
     */
    public function getJobExpiration($job)
    {
        if (! method_exists($job, 'retryUntil') && ! isset($job->timeoutAt)) {
            return;
        }

        $expiration = $job->timeoutAt ?? $job->retryUntil();

        return $expiration instanceof DateTimeInterface
            ? $expiration->getTimestamp() : $expiration;
    }

    /**
     * Create a typical, string based queue payload array.
     *
     * @param  string $job
     * @param  mixed  $data
     * @return array
     */
    protected function createStringPayload($job, $data)
    {
        return [
            'displayName' => is_string($job) ? explode('@', $job)[0] : null,
            'job'         => $job, 'maxTries' => null,
            'timeout'     => null, 'data' => $data,
        ];
    }
}