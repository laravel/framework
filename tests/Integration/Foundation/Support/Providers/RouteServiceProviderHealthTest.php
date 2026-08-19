<?php

namespace Illuminate\Tests\Integration\Foundation\Support\Providers;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;
use RuntimeException;

#[WithConfig('app.debug', false)]
#[WithConfig('filesystems.disks.local.serve', false)]
class RouteServiceProviderHealthTest extends TestCase
{
    /**
     * Resolve application implementation.
     *
     * @return \Illuminate\Foundation\Application
     */
    protected function resolveApplication()
    {
        return Application::configure(static::applicationBasePath())
            ->withRouting(
                web: __DIR__.'/fixtures/web.php',
                health: '/up',
            )->create();
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', Str::random(32));
    }

    public function test_it_can_load_health_page()
    {
        $this->get('/up')
            ->assertOk()
            ->assertSee('Application up');
    }

    public function test_it_returns_json_when_request_expects_json()
    {
        $this->getJson('/up')
            ->assertOk()
            ->assertExactJson(['status' => 'up']);
    }

    public function test_it_returns_json_failure_status_when_diagnosis_reports_a_problem()
    {
        Event::listen(DiagnosingHealth::class, function () {
            throw new RuntimeException('Database connection refused.');
        });

        $this->getJson('/up')
            ->assertStatus(500)
            ->assertExactJson(['status' => 'down']);
    }

    public function test_it_renders_html_failure_page_when_diagnosis_reports_a_problem()
    {
        Event::listen(DiagnosingHealth::class, function () {
            throw new RuntimeException('Database connection refused.');
        });

        $this->get('/up')
            ->assertStatus(500)
            ->assertSee('experiencing problems');
    }
}
