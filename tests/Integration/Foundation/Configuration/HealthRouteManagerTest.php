<?php

namespace Illuminate\Tests\Integration\Foundation\Configuration;

use Exception;
use Illuminate\Foundation\Configuration\HealthRouteManager;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class HealthRouteManagerTest extends TestCase
{
    public function testHealthRouteIsRegistered()
    {
        $this->assertNotContains('health', $this->getRegisteredRoutes());

        HealthRouteManager::register('health');

        $this->assertContains('health', $this->getRegisteredRoutes());
    }

    public function testReturnsTheDefaultHealthViewByDefault()
    {
        HealthRouteManager::register('health');

        $view = str_replace('/tests/Integration/', '/src/Illuminate/', __DIR__.'/../resources/health-up.blade.php');

        $this->get('health')
            ->assertViewIs($view)
            ->assertViewHas('exception');
    }

    public function testCanHaveACustomResponseRegistered()
    {
        HealthRouteManager::register('health');
        HealthRouteManager::respondUsing(fn () => ['ok' => true]);

        $this->get('health')->assertJson(['ok' => true]);
    }

    public function testCanAccessTheExceptionMessageInTheRespondUsingCallback()
    {
        HealthRouteManager::register('health');
        HealthRouteManager::respondUsing(fn ($exception) => [
            'ok' => false,
            'exception' => $exception,
        ]);

        Event::listen(DiagnosingHealth::class, function () {
            throw new Exception('Uh oh, something went wrong');
        });

        $this->get('health')->assertJson([
            'ok' => false,
            'exception' => 'Uh oh, something went wrong',
        ]);
    }

    public function testTheDefaultResponseHasTheExceptionMessageAndReturns500IfItIsSet()
    {
        HealthRouteManager::register('health');

        Event::listen(DiagnosingHealth::class, function () {
            throw new Exception('Uh oh, something went wrong');
        });

        $this->get('health')
            ->assertServerError()
            ->assertViewHas('exception', 'Uh oh, something went wrong');
    }

    protected function getRegisteredRoutes(): Collection
    {
        return collect(Route::getRoutes()->getRoutes())->map(fn (\Illuminate\Routing\Route $route) => $route->uri());
    }

    protected function tearDown(): void
    {
        // Reset the state of the health route manager
        $reflection = new \ReflectionClass(HealthRouteManager::class);

        $prop = $reflection->getProperty('responseCallback');
        $prop->setAccessible(true);
        $prop->setValue(null);

        parent::tearDown();
    }
}
