<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as FoundationTestCase;
use Illuminate\Support\Facades\Facade;
use Illuminate\Testing\ParallelTesting;
use PHPUnit\Framework\TestCase;
use stdClass;

class InteractsWithTestCaseLifecycleTest extends TestCase
{
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
}
