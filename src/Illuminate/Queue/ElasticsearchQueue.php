<?php

namespace Illuminate\Queue;

use DateTime;
use Carbon\Carbon;
use Elasticsearch\Client;
use Illuminate\Support\Str;
use Illuminate\Queue\Jobs\ElasticsearchJob;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class ElasticsearchQueue extends Queue implements QueueContract
{
    /**
     * The elasticsearch connection instance.
     *
     * @var \Elasticsearch\Client
     */
    protected $elasticsearch;

    /**
     * The elasticsearch index that holds the jobs.
     *
     * @var string
     */
    protected $index;

    /**
     * The name of the default queue.
     *
     * @var string
     */
    protected $default;

    /**
     * The expiration time of a job.
     *
     * @var int|null
     */
    protected $expire = 60;

    /**
     * Create a new elasticsearch queue instance.
     *
     * @param  \Elasticsearch\Client  $elasticsearch
     * @param  string  $index
     * @param  string  $default
     * @param  int  $expire
     */
    public function __construct(Client $elasticsearch, $index, $default = 'default', $expire = 60)
    {
        $this->index = $index;
        $this->expire = $expire;
        $this->default = $default;
        $this->elasticsearch = $elasticsearch;
    }

    /**
     * Get the size of the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function size($queue = null)
    {
        $result = $this->elasticsearch->search(['index' => $this->index, 'type' => $this->getQueue($queue)]);

        return $result['hits']['total'];
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        return $this->pushToElasticsearch(0, $queue, $payload);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return void
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->pushToElasticsearch($delay, $queue, $this->createPayload($job, $data));
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param  array   $jobs
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        $queue = $this->getQueue($queue);

        $availableAt = $this->getAvailableAt(0);

        $records = array_map(function ($job) use ($queue, $data, $availableAt) {
            return $this->buildElasticsearchDoc(
                $queue, $this->createPayload($job, $data), $availableAt
            );
        }, (array) $jobs);

        return $this->elasticsearch->index($this->index)->insert($records);
    }

    /**
     * Release a reserved job back onto the queue.
     *
     * @param  string  $queue
     * @param  \StdClass  $job
     * @param  int  $delay
     * @return mixed
     */
    public function release($queue, $job, $delay)
    {
        return $this->pushToElasticsearch($delay, $queue, $job->payload, $job->attempts);
    }

    /**
     * Push a raw payload to the elasticsearch with a given delay.
     *
     * @param  \DateTime|int  $delay
     * @param  string|null  $queue
     * @param  string  $payload
     * @param  int  $attempts
     * @return mixed
     */
    protected function pushToElasticsearch($delay, $queue, $payload, $attempts = 0)
    {
        $queue = $this->getQueue($queue);

        $attributes = $this->buildElasticsearchDoc(
            $queue, $payload, $this->getAvailableAt($delay), $attempts
        );

        $params['index'] = $this->index;
        $params['type'] = $queue;
        $params['id'] = $attributes['id'];
        $params['body'] = $attributes;

        $this->elasticsearch->index($params);

        return $attributes['id'];
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        if ($job = $this->getNextAvailableJob($queue)) {
            $job = $this->markJobAsReserved($job, $queue);

            return new ElasticsearchJob(
                $this->container, $this, $job, $queue
            );
        }
    }

    /**
     * Get the next available job for the queue.
     *
     * @param  string|null  $queue
     * @return \StdClass|null
     */
    protected function getNextAvailableJob($queue)
    {
        $job = false;

        $params['index'] = $this->index;
        $params['type'] = $this->getQueue($queue);
        $params['body'] = [
          'query' => [
              'range' => [
                  'reserved_at' => [
                      'lte' => Carbon::now()->subSeconds($this->expire)->getTimestamp(),
                  ],
              ],
          ],
        ];
        $params['size'] = 1;
        $params['sort'] = ['reserved_at:desc', 'available_at:desc'];

        $result = $this->elasticsearch->search($params);

        if ($result['hits']['total']) {
            $job = $result['hits']['hits'][0]['_source'];
        }

        return $job ? (object) $job : null;
    }

    /**
     * Mark the given job ID as reserved.
     *
     * @param $job
     * @param $queue
     * @return mixed
     */
    protected function markJobAsReserved($job, $queue = null)
    {
        $job->attempts = $job->attempts + 1;
        $job->reserved_at = $this->getTime();

        $params['index'] = $this->index;
        $params['type'] = $this->getQueue($queue);
        $params['id'] = $job->id;
        $params['body']['doc'] = [
            'reserved_at' => $job->reserved_at,
            'attempts' => $job->attempts,
        ];

        $this->elasticsearch->update($params);

        return $job;
    }

    /**
     * Delete a reserved job from the queue.
     *
     * @param  string  $queue
     * @param  string  $id
     * @return void
     */
    public function deleteReserved($queue, $id)
    {
        $params['index'] = $this->index;
        $params['type'] = $queue;
        $params['id'] = $id;

        $this->elasticsearch->delete($params);
    }

    /**
     * Get the "available at" UNIX timestamp.
     *
     * @param  \DateTime|int  $delay
     * @return int
     */
    protected function getAvailableAt($delay)
    {
        $availableAt = $delay instanceof DateTime ? $delay : Carbon::now()->addSeconds($delay);

        return $availableAt->getTimestamp();
    }

    /**
     * Create an array to insert for the given job.
     *
     * @param  string|null  $queue
     * @param  string  $payload
     * @param  int  $availableAt
     * @param  int  $attempts
     * @return array
     */
    protected function buildElasticsearchDoc($queue, $payload, $availableAt, $attempts = 0)
    {
        return [
            'id' => $this->retrieveFromPayload('id', $payload),
            'queue' => $this->getQueue($queue),
            'attempts' => $attempts,
            'reserved_at' => 0,
            'available_at' => $availableAt,
            'created_at' => $this->getTime(),
            'payload' => $payload,
        ];
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return string
     */
    protected function createPayload($job, $data = '', $queue = null)
    {
        $payload = $this->setMeta(
            parent::createPayload($job, $data), 'id', $this->getRandomId()
        );

        return $this->setMeta($payload, 'attempts', 1);
    }

    /**
     * @param $field
     * @param $payload
     * @return mixed
     */
    protected function retrieveFromPayload($field, $payload)
    {
        $payload = json_decode($payload);

        return $payload->$field;
    }

    /**
     * Get a random ID string.
     *
     * @return string
     */
    protected function getRandomId()
    {
        return Str::random(32);
    }

    /**
     * Get the queue or return the default.
     *
     * @param  string|null  $queue
     * @return string
     */
    protected function getQueue($queue)
    {
        return $queue ?: $this->default;
    }

    /**
     * Get the underlying elasticsearch instance.
     *
     * @return \Elasticsearch\Client
     */
    public function getElasticsearch()
    {
        return $this->elasticsearch;
    }
}
