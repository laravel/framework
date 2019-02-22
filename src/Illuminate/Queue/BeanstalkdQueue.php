<?php

namespace Illuminate\Queue;

use Pheanstalk\Pheanstalk;
use Pheanstalk\Job as PheanstalkJob;
use Illuminate\Queue\Jobs\BeanstalkdJob;
use Pheanstalk\Contract\PheanstalkInterface;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class BeanstalkdQueue extends Queue implements QueueContract
{
    /**
     * The Pheanstalk instance.
     *
     * @var \Pheanstalk\Pheanstalk
     */
    protected $pheanstalk;

    /**
     * The name of the default tube.
     *
     * @var string
     */
    protected $default;

    /**
     * The "time to run" for all pushed jobs.
     *
     * @var int
     */
    protected $timeToRun;

    /**
     * Create a new Beanstalkd queue instance.
     *
     * @param  \Pheanstalk\Pheanstalk  $pheanstalk
     * @param  string  $default
     * @param  int  $timeToRun
     * @return void
     */
    public function __construct(Pheanstalk $pheanstalk, $default, $timeToRun)
    {
        $this->default = $default;
        $this->timeToRun = $timeToRun;
        $this->pheanstalk = $pheanstalk;
    }

    /**
     * Get the size of the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function size($queue = null)
    {
        $queue = $this->getQueue($queue);

        return (int) $this->pheanstalk->statsTube($queue)->current_jobs_ready;
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
        return $this->pushRaw($this->createPayload($job, $this->getQueue($queue), $data), $queue);
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
        return $this->pheanstalk->useTube($this->getQueue($queue))->put(
            $payload, Pheanstalk::DEFAULT_PRIORITY, Pheanstalk::DEFAULT_DELAY, $this->timeToRun
        );
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $pheanstalk = $this->pheanstalk->useTube($this->getQueue($queue));

        return $pheanstalk->put(
            $this->createPayload($job, $this->getQueue($queue), $data),
            Pheanstalk::DEFAULT_PRIORITY,
            $this->secondsUntil($delay),
            $this->timeToRun
        );
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

        $this->pheanstalk->watchOnly($queue);

        $job = interface_exists(PheanstalkInterface::class)
                    ? $this->pheanstalk->reserveWithTimeout(0)
                    : $this->pheanstalk->reserve(0);

        if ($job instanceof PheanstalkJob) {
            return new BeanstalkdJob(
                $this->container, $this->pheanstalk, $job, $this->connectionName, $queue
            );
        }
    }

    /**
     * Delete a message from the Beanstalk queue.
     *
     * @param  string  $queue
     * @param  string  $id
     * @return void
     */
    public function deleteMessage($queue, $id)
    {
        $queue = $this->getQueue($queue);

        $this->pheanstalk->useTube($queue)->delete(new PheanstalkJob($id, ''));
    }

    /**
     * Get the queue or return the default.
     *
     * @param  string|null  $queue
     * @return string
     */
    public function getQueue($queue)
    {
        return $queue ?: $this->default;
    }

    /**
     * Get the underlying Pheanstalk instance.
     *
     * @return \Pheanstalk\Pheanstalk
     */
    public function getPheanstalk()
    {
        return $this->pheanstalk;
    }
}
