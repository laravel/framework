<?php

namespace Illuminate\Tests\Integration\Queue;

use Orchestra\Testbench\TestCase;

abstract class QueueTestCase extends TestCase
{
    /**
     * The current database driver.
     *
     * @return string
     */
    protected $driver;

    protected function defineEnvironment($app)
    {
        $this->driver = $app['config']->get('queue.default');
    }
}
