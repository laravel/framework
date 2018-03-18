<?php

namespace Illuminate\Contracts\Queue;

interface PayloadSerializer
{
    /**
     * @param array $payloadArray
     * @return string
     */
    public function serialize($payloadArray);

    /**
     * @param string $raw
     * @param string $connectionName
     * @param string $queueName
     * @return array
     */
    public function unserialize($raw, $connectionName, $queueName);

    /**
     * Create a payload array from the given job and data.
     *
     * @param  string $connectionName
     * @param  string $queueName
     * @param  string $job
     * @param  mixed  $data
     * @return array
     */
    public function createPayloadArray($connectionName, $queueName, $job, $data = '');
}
