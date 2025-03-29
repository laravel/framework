<?php

namespace Illuminate\Tests\Integration\Concurrency;

use Exception;
use Illuminate\Concurrency\KafkaDriver;
use Illuminate\Support\Facades\Concurrency;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('rdkafka')]
class KafkaDriverTest extends TestCase
{
    /**
     * @var \Illuminate\Concurrency\KafkaDriver
     */
    protected $driver;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('rdkafka')) {
            $this->markTestSkipped('The rdkafka extension is not available.');
        }

        // Skip tests in CI environments
        if (getenv('CI') || getenv('GITHUB_ACTIONS')) {
            $this->markTestSkipped('Skipping Kafka tests in CI environment.');
        }

        $this->driver = new KafkaDriver(
            'localhost:9092',
            'test-tasks',
            'test-results',
            'test-deferred',
            'test-group'
        );
    }

    public function testRunMethodWithSingleTask()
    {
        if (! extension_loaded('rdkafka')) {
            $this->markTestSkipped('The rdkafka extension is not available.');
        }

        // Skip tests in CI environments
        if (getenv('CI') || getenv('GITHUB_ACTIONS')) {
            $this->markTestSkipped('Skipping Kafka tests in CI environment.');
        }

        // Start the Kafka processor command manually before running this test
        // php artisan concurrency:kafka-processor

        $result = $this->driver->run(fn () => 2 + 2);

        $this->assertEquals([4], $result);
    }

    public function testRunMethodWithMultipleTasks()
    {
        if (! extension_loaded('rdkafka')) {
            $this->markTestSkipped('The rdkafka extension is not available.');
        }

        // Skip tests in CI environments
        if (getenv('CI') || getenv('GITHUB_ACTIONS')) {
            $this->markTestSkipped('Skipping Kafka tests in CI environment.');
        }

        // Start the Kafka processor command manually before running this test
        // php artisan concurrency:kafka-processor

        $result = $this->driver->run([
            fn () => 1 + 1,
            fn () => 2 + 2,
            fn () => 3 + 3,
        ]);

        $this->assertEquals([2, 4, 6], $result);
    }

    public function testErrorHandlingInRunMethod()
    {
        if (! extension_loaded('rdkafka')) {
            $this->markTestSkipped('The rdkafka extension is not available.');
        }

        // Skip tests in CI environments
        if (getenv('CI') || getenv('GITHUB_ACTIONS')) {
            $this->markTestSkipped('Skipping Kafka tests in CI environment.');
        }

        // Start the Kafka processor command manually before running this test
        // php artisan concurrency:kafka-processor

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        $this->driver->run([
            fn () => throw new Exception('Test exception'),
        ]);
    }

    public function testDeferMethod()
    {
        if (! extension_loaded('rdkafka')) {
            $this->markTestSkipped('The rdkafka extension is not available.');
        }

        // Start the Kafka processor command manually before running this test
        // php artisan concurrency:kafka-processor

        $deferred = $this->driver->defer(fn () => 2 + 2);

        $this->assertIsObject($deferred);
    }
} 