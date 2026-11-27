<?php

namespace Illuminate\Tests\Integration\Foundation\Configuration;

use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Orchestra\Testbench\TestCase;
use ReflectionProperty;

class WithCommandRoutingTest extends TestCase
{
    protected function resolveApplication()
    {
        return Application::configure(static::applicationBasePath())
            ->withRouting(commands: __DIR__.'/Fixtures/console.php')
            ->create();
    }

    public function testCommandRoutesAreLoaded()
    {
        $this->assertArrayHasKey('test:inspire', $this->app->make(ConsoleKernel::class)->all());
    }

    public function testResolvingTheConsoleKernelInAClonedApplicationDoesNotRetainItOnTheBaseApplication()
    {
        $this->app->forgetInstance(ConsoleKernel::class);

        $bootedCallbacks = new ReflectionProperty(Application::class, 'bootedCallbacks');
        $before = count($bootedCallbacks->getValue($this->app));

        foreach (range(1, 3) as $request) {
            $sandbox = clone $this->app;
            $this->setCurrentApplication($sandbox);

            try {
                $this->assertArrayHasKey('test:inspire', $sandbox->make(ConsoleKernel::class)->all());
            } finally {
                $this->setCurrentApplication($this->app);
            }
        }

        $this->assertCount($before, $bootedCallbacks->getValue($this->app));
    }

    protected function setCurrentApplication(Application $app)
    {
        $app->instance('app', $app);
        $app->instance(Container::class, $app);

        Container::setInstance($app);

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);
    }
}
