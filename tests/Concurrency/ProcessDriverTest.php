<?php

namespace Illuminate\Tests\Concurrency;

use Exception;
use Illuminate\Concurrency\ProcessDriver;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Process\Factory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ProcessDriverTest extends TestCase
{
    public function testRunRecreatesExceptionsFromCapturedParametersIncludingNulls()
    {
        try {
            $this->runWithFakeResult([
                'successful' => false,
                'exception' => DriverNullableParameterException::class,
                'message' => 'Exception with nullable parameter',
                'parameters' => ['status' => null],
            ]);

            $this->fail('The expected exception was not thrown.');
        } catch (DriverNullableParameterException $e) {
            $this->assertNull($e->status);
        }
    }

    public function testRunRecreatesExceptionsFromTheMessageWhenNoParametersWereCaptured()
    {
        try {
            $this->runWithFakeResult([
                'successful' => false,
                'exception' => DriverUnbackedParameterException::class,
                'message' => 'Failed in payments',
                'parameters' => [],
            ]);

            $this->fail('The expected exception was not thrown.');
        } catch (DriverUnbackedParameterException $e) {
            $this->assertStringContainsString('Failed in payments', $e->getMessage());
        }
    }

    protected function runWithFakeResult(array $result): void
    {
        $factory = new Factory;

        $factory->fake(fn () => $factory->result(json_encode($result)));

        $previousContainer = Container::getInstance();

        Container::setInstance(new Application(__DIR__));

        try {
            (new ProcessDriver($factory))->run(fn () => null);
        } finally {
            Container::setInstance($previousContainer);
        }
    }
}

class DriverNullableParameterException extends RuntimeException
{
    public function __construct(public ?int $status = null)
    {
        parent::__construct('Exception with nullable parameter');
    }
}

class DriverUnbackedParameterException extends Exception
{
    public function __construct(string $context)
    {
        parent::__construct("Failed in {$context}");
    }
}
