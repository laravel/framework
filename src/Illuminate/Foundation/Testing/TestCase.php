<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\Attributes\UnitTest;
use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionMethod;
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
     * The list of trait that this test uses, fetched recursively.
     *
     * @var array<class-string, int>
     */
    protected array $traitsUsedByTest;

    /**
     * Memoized result of the withoutBootingFramework check.
     *
     * @var bool|null
     */
    protected ?bool $withoutBootingFramework = null;

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $this->traitsUsedByTest = class_uses_recursive(static::class);

        if (isset(CachedState::$cachedConfig, $this->traitsUsedByTest[WithCachedConfig::class])) {
            $this->markConfigCached($app);
        }

        if (isset(CachedState::$cachedRoutes, $this->traitsUsedByTest[WithCachedRoutes::class])) {
            $app->booting(fn () => $this->markRoutesCached($app));
        }

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if ($this->withoutBootingFramework()) {
            return;
        }

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
     * Clean up the testing environment before the next test.
     *
     * @return void
     *
     * @throws \Mockery\Exception\InvalidCountException
     */
    protected function tearDown(): void
    {
        if ($this->withoutBootingFramework()) {
            return;
        }

        $this->tearDownTheTestEnvironment();
    }

    /**
     * Determine if the test method should boot the framework.
     *
     * @return bool
     *
     * @throws \ReflectionException
     */
    protected function withoutBootingFramework(): bool
    {
        if ($this->withoutBootingFramework !== null) {
            return $this->withoutBootingFramework;
        }

        try {
            return $this->withoutBootingFramework = (new ReflectionMethod(static::class, $this->name()))->getAttributes(UnitTest::class) !== [];
        } catch (Throwable) {
            return $this->withoutBootingFramework = false;
        }
    }

    /**
     * Clean up the testing environment before the next test case.
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        static::tearDownAfterClassUsingTestCase();
    }
}
