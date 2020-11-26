<?php

namespace Illuminate\Tests\Foundation\Bootstrap;

use const E_ALL;
use function error_reporting;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Mockery as m;
use PHPUnit\Framework\TestCase;

/**
 * This test is set to run in isolation to avoid messing up with the test environment and affecting other tests.
 *
 * @runInSeparateProcess
 */
class HandleExceptionsTest extends TestCase
{
    /** @var Application */
    private $app;

    /** @before */
    public function configureApp(): void
    {
        $this->app = m::mock(Application::class);
        $this->app->allows()->environment('testing')->andReturns(false);
    }

    /** @test */
    public function errorReportingShouldNotBeAffectedOnProduction(): void
    {
        error_reporting(E_ALL);

        $this->app->allows()->environment('production')->andReturns(true);

        (new HandleExceptions())->bootstrap($this->app);

        self::assertSame(E_ALL, error_reporting());
    }

    /** @test */
    public function errorReportingShouldBeOverriddenForOtherEnvironmentSoPeopleCanFindBugsEarlier(): void
    {
        error_reporting(E_ALL);

        $this->app->allows()->environment('production')->andReturns(false);

        (new HandleExceptions())->bootstrap($this->app);

        self::assertSame(-1, error_reporting());
    }
}
