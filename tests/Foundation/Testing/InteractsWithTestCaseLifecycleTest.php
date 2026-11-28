<?php

namespace Illuminate\Tests\Foundation\Testing;

use Closure;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as FoundationTestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Facade;
use Illuminate\Testing\ParallelTesting;
use JMac\Testing\Double;
use Mockery as m;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestStatus\TestStatus;
use ReflectionProperty;
use stdClass;

class InteractsWithTestCaseLifecycleTest extends TestCase
{
    protected function tearDown(): void
    {
        Facade::useMockery();

        parent::tearDown();
    }

    public function testDatabaseConnectionsAreDisconnectedWhenTheTestEnvironmentIsTornDown()
    {
        $application = new Application;

        $application->instance('db', new DatabaseManager($application, new ConnectionFactory($application)));

        $connection = $application['db']->connectUsing('testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->assertNotNull($connection->getPdo());

        $this->tearDownApplication($application);

        $this->assertNull($connection->getRawPdo());
    }

    public function testTearDownWithoutDatabaseManager()
    {
        $application = new Application;

        $application->instance('db', new stdClass);

        $this->tearDownApplication($application);

        $this->assertFalse($application->bound('db'));
    }

    public function testTearDownWithMockedDatabaseManager()
    {
        $application = new Application;

        $application->instance('db', m::mock(DatabaseManager::class));

        $this->tearDownApplication($application);

        $this->assertFalse($application->bound('db'));
    }

    public function testTearDownWithDoubledDatabaseManager()
    {
        $application = new Application;

        $application->instance('db', Double::for(DatabaseManager::class)->strict());

        $this->tearDownApplication($application);

        $this->assertFalse($application->bound('db'));
    }

    public function testUnmetDoubleExpectationFailsTheTest()
    {
        Facade::useDouble();

        $this->expectException(AssertionFailedError::class);

        $this->runTestCase(fn () => Event::expects('dispatch'), TestStatus::success());
    }

    public function testMetDoubleExpectationPasses()
    {
        Facade::useDouble();

        $this->runTestCase(function () {
            Event::expects('dispatch');

            Event::dispatch('foo');
        }, TestStatus::success());

        $this->assertSame(0, Double::verifyAll());
    }

    public function testFailedTestSkipsDoubleVerification()
    {
        Facade::useDouble();

        $this->runTestCase(fn () => Event::expects('dispatch'), TestStatus::failure('failed'));

        $this->assertSame(0, Double::verifyAll());
    }

    public function testDoublesAreNotVerifiedWhenUsingMockery()
    {
        Double::enableAutoVerify();

        $this->runTestCase(fn () => Double::for(Dispatcher::class)->expects('dispatch'), TestStatus::success());

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('it was never called');

        Double::verifyAll();
    }

    private function tearDownApplication(Application $application): void
    {
        $application->instance(ParallelTesting::class, new ParallelTesting($application));

        $previous = Facade::getFacadeApplication();

        Facade::setFacadeApplication($application);

        try {
            $testCase = new class('foo') extends FoundationTestCase
            {
                public function createApplication()
                {
                    return new Application;
                }

                public function tearDownEnvironment(Application $application)
                {
                    $this->app = $application;

                    $this->tearDownTheTestEnvironment();
                }
            };

            $testCase->tearDownEnvironment($application);
        } finally {
            Facade::setFacadeApplication($previous);
            Facade::clearResolvedInstances();
        }
    }

    private function runTestCase(Closure $callback, TestStatus $status): void
    {
        $previous = Facade::getFacadeApplication();

        try {
            $testCase = new class('foo') extends FoundationTestCase
            {
                public function createApplication()
                {
                    $application = new Application;

                    $application->instance(ParallelTesting::class, new ParallelTesting($application));

                    Facade::setFacadeApplication($application);

                    return $application;
                }

                public function runEnvironment(Closure $callback, TestStatus $status)
                {
                    $this->setUpTheTestEnvironment();

                    $callback();

                    (new ReflectionProperty(TestCase::class, 'status'))->setValue($this, $status);

                    $this->tearDownTheTestEnvironment();
                }
            };

            $testCase->runEnvironment($callback, $status);
        } finally {
            Facade::setFacadeApplication($previous);
            Facade::clearResolvedInstances();
        }
    }
}
