<?php

namespace Illuminate\Queue\Connectors;

use Illuminate\Queue\BeanstalkdQueue;
use Illuminate\Support\Arr;
use Pheanstalk\Connection;
use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;

class BeanstalkdConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param array $config
     *
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
     * @param array $config
     *
     * @return \Pheanstalk\Pheanstalk
     */
    protected function pheanstalk(array $config)
    {
        return new Pheanstalk(
            $config['host'],
            Arr::get($config, 'port', PheanstalkInterface::DEFAULT_PORT),
            Arr::get($config, 'timeout', Connection::DEFAULT_CONNECT_TIMEOUT),
            Arr::get($config, 'persistent', false)
        );
    }
}
