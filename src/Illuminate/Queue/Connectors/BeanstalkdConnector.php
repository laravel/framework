<?php

namespace Illuminate\Queue\Connectors;

use Pheanstalk\Connection;
use Pheanstalk\Pheanstalk;
use Illuminate\Support\Arr;
use Pheanstalk\PheanstalkInterface;
use Illuminate\Queue\BeanstalkdQueue;

class BeanstalkdConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $retryAfter = Arr::get($config, 'retry_after', Pheanstalk::DEFAULT_TTR);

        return new BeanstalkdQueue($this->pheanstalk($config), $config['queue'], $retryAfter);
    }

    /**
     * Create a Pheanstalk instance.
     *
     * @param  array  $config
     * @return \Pheanstalk\Pheanstalk
     */
    protected function pheanstalk(array $config)
    {
        $port = Arr::get($config, 'port', PheanstalkInterface::DEFAULT_PORT);
        $timeout = Arr::get($config, 'timeout', Connection::DEFAULT_CONNECT_TIMEOUT);
        $persistent = Arr::get($config, 'persistent', false);

        return new Pheanstalk($config['host'], $port, $timeout, $persistent);
    }
}
