<?php

namespace Illuminate\Foundation\Cloud;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Traits\Macroable;
use RuntimeException;

class CloudManager
{
    use Macroable;

    /**
     * Create a new Laravel Cloud manager instance.
     */
    public function __construct(protected Container $container)
    {
    }

    /**
     * Determine if the application is currently hosted on Laravel Cloud.
     */
    public function hosted(): bool
    {
        return laravel_cloud();
    }

    /**
     * Determine if the application is using Laravel Cloud managed queues.
     */
    public function usesManagedQueues(): bool
    {
        return $this->container->make('config')->get('queue.connections.cloud.driver') === 'cloud';
    }

    /**
     * Get the Laravel Cloud managed queue connection.
     *
     * @throws \RuntimeException
     */
    public function queue(): Queue
    {
        if (! $this->usesManagedQueues()) {
            throw new RuntimeException('Laravel Cloud managed queues are not configured for this application.');
        }

        return $this->container->make('queue')->connection('cloud');
    }
}
