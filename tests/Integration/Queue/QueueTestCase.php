<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Orchestra\Testbench\TestCase;

abstract class QueueTestCase extends TestCase
{
    use DatabaseMigrations;

    /**
     * The current database driver.
     *
     * @return string
     */
    protected $driver;

    protected function defineEnvironment($app)
    {
        $this->driver = $app['config']->get('queue.default', 'sync');
    }

    protected function runQueueWorkerCommand(array $options = [], int $times = 1): void
    {
        if ($this->getQueueDriver() !== 'sync' && $times > 0) {
            $count = 0;

            do {
                $this->artisan('queue:work', array_merge($options, [
                    '--memory' => 1024,
                ]))->assertSuccessful();

                $count++;
            } while ($count < $times);
        }
    }

    protected function getQueueDriver(): string
    {
        return $this->driver;
    }
}
