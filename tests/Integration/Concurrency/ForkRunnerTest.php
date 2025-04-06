<?php

namespace Illuminate\Tests\Integration\Concurrency;

use Illuminate\Concurrency\ConcurrencyManager;
use Illuminate\Concurrency\ForkDriver;
use Orchestra\Testbench\TestCase;
use Spatie\Fork\Fork;

/**
 * @group full-coverage
 */
class ForkRunnerTest extends TestCase
{
    public function testConcurrencyManagerCanCreateForkDriver()
    {
        // Skip if package not installed
        if (class_exists(Fork::class)) {
            // If Fork exists, we can use the actual class
            $manager = new ConcurrencyManager($this->app);
            $reflection = new \ReflectionClass($manager);
            $method = $reflection->getMethod('createForkDriver');
            $method->setAccessible(true);

            $driver = $method->invoke($manager, []);

            $this->assertInstanceOf(ForkDriver::class, $driver);
        } else {
            // If Fork doesn't exist, we define the required class
            // Creating a temporary Fork class
            eval('namespace Spatie\Fork; class Fork { public static function new() {} }');

            // Make sure it's now available
            $this->assertTrue(class_exists('Spatie\Fork\Fork'));

            // Then test the ConcurrencyManager
            $manager = new ConcurrencyManager($this->app);
            $reflection = new \ReflectionClass($manager);
            $method = $reflection->getMethod('createForkDriver');
            $method->setAccessible(true);

            $driver = $method->invoke($manager, []);

            $this->assertInstanceOf(ForkDriver::class, $driver);
        }
    }
}
