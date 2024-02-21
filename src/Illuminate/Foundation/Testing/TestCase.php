<?php

namespace Illuminate\Foundation\Testing;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Throwable;

abstract class TestCase extends BaseTestCase
{
    use Concerns\InteractsWithContainer,
        Concerns\MakesHttpRequests,
        Concerns\InteractsWithAuthentication,
        Concerns\InteractsWithConsole,
        Concerns\InteractsWithDatabase,
        Concerns\InteractsWithDeprecationHandling,
        Concerns\InteractsWithExceptionHandling,
        Concerns\InteractsWithSession,
        Concerns\InteractsWithTime,
        Concerns\InteractsWithTestCaseLifecycle,
        Concerns\InteractsWithViews;

    /**
     * Creates the application.
     *
     * Needs to be implemented by subclasses.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    abstract public function createApplication();

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        static::$latestResponse = null;

        $this->setUpTheTestEnvironment();
    }

    /**
     * Refresh the application instance.
     *
     * @return void
     */
    protected function refreshApplication()
    {
        $this->app = $this->createApplication();
    }

    /**
     * {@inheritdoc}
     */
    protected function runTest(): mixed
    {
        $result = null;

        try {
            $result = parent::runTest();
        } catch (Throwable $e) {
            if (! is_null(static::$latestResponse)) {
                static::$latestResponse->transformNotSuccessfulException($e);
            }

            throw $e;
        }

        return $result;
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     *
     * @throws \Mockery\Exception\InvalidCountException
     */
    protected function tearDown(): void
    {
        $this->tearDownTheTestEnvironment();
    }

    /**
     * Clean up the testing environment before the next test case.
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        static::$latestResponse = null;

        static::tearDownAfterClassUsingTestCase();
    }
}
