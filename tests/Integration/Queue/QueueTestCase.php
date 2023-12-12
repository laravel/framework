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

    /**
     * Define the test environment.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        $this->driver = $app['config']->get('queue.default', 'sync');
    }

    /**
     * Run queue worker command.
     *
     * @param  array  $options
     * @param  int  $times
     * @return void
     */
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

    /**
     * Mark test as skipped when using "sync" queue driver.
     *
     * @return void
     */
    protected function markTestSkippedWhenUsingSyncQueueDriver(): void
    {
        if ($this->getQueueDriver() === 'sync') {
            $this->markTestSkipped('Unable to use `sync` queue driver for the test');
        }
    }

    /**
     * Get the queue driver.
     *
     * @return string
     */
    protected function getQueueDriver(): string
    {
        return $this->driver;
    }
}
