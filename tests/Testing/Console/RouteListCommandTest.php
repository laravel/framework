<?php

namespace Illuminate\Tests\Testing\Console;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDeprecationHandling;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;

#[WithConfig('filesystems.disks.local.serve', false)]
class RouteListCommandTest extends TestCase
{
    use InteractsWithDeprecationHandling;

    /**
     * @var \Illuminate\Contracts\Routing\Registrar
     */
    private $router;

    /**
     * @var \Illuminate\Routing\UrlGenerator
     */
    private $urlGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = $this->app->make(Registrar::class);

        RouteListCommand::resolveTerminalWidthUsing(function () {
            return 70;
        });
    }

    public function testDisplayRoutesForCli()
    {
        $this->withoutMockingConsoleOutput();

        RouteListCommand::resolveTerminalWidthUsing(fn () => 200);

        $closureLine = __LINE__ + 1;
        $this->router->get('/', function () {
            //
        });

        $this->router->get('closure', function () {
            return new RedirectResponse($this->urlGenerator->signedRoute('signed-route'));
        });

        $this->router->get('controller-method/{user}', [FooController::class, 'show']);
        $this->router->post('controller-invokable', FooController::class);
        $this->router->domain('{account}.example.com')->group(function () {
            $this->router->get('/', function () {
                //
            });

            $this->router->get('user/{id}', function ($account, $id) {
                //
            })->name('user.show')->middleware('web');
        });

        $this->artisan(RouteListCommand::class);
        $output = Artisan::output();

        $this->assertStringContainsString('GET|HEAD', $output);
        $this->assertStringContainsString('closure', $output);
        $this->assertStringContainsString('controller-invokable', $output);
        $this->assertStringContainsString('controller-method/{user}', $output);
        $this->assertStringContainsString('RouteListCommandTest.php:'.$closureLine, $output);
        $this->assertStringContainsString('Showing [6] routes', $output);
    }

    public function testDisplayRoutesForCliInVerboseMode()
    {
        $this->withoutMockingConsoleOutput();

        RouteListCommand::resolveTerminalWidthUsing(fn () => 200);

        $closureLine = __LINE__ + 1;
        $this->router->get('closure', function () {
            return new RedirectResponse($this->urlGenerator->signedRoute('signed-route'));
        });

        $this->router->get('controller-method/{user}', [FooController::class, 'show']);
        $this->router->post('controller-invokable', FooController::class);
        $this->router->domain('{account}.example.com')->group(function () {
            $this->router->get('user/{id}', function ($account, $id) {
                //
            })->name('user.show')->middleware('web');
        });

        $this->artisan(RouteListCommand::class, ['-v' => true]);
        $output = Artisan::output();

        $this->assertStringContainsString('closure', $output);
        $this->assertStringContainsString('RouteListCommandTest.php:'.$closureLine, $output);
        $this->assertStringContainsString('controller-invokable', $output);
        $this->assertStringContainsString('FooController@show', $output);
        $this->assertStringContainsString('user.show', $output);
        $this->assertStringContainsString('web', $output);
        $this->assertStringContainsString('Showing [4] routes', $output);
    }

    public function testRouteCanBeFilteredByName()
    {
        $this->withoutDeprecationHandling();
        $this->withoutMockingConsoleOutput();

        RouteListCommand::resolveTerminalWidthUsing(fn () => 200);

        $this->router->get('/', function () {
            //
        });
        $closureLine = __LINE__ + 1;
        $this->router->get('/foo', function () {
            //
        })->name('foo.show');

        $this->artisan(RouteListCommand::class, ['--name' => 'foo']);
        $output = Artisan::output();

        $this->assertStringContainsString('foo', $output);
        $this->assertStringContainsString('foo.show', $output);
        $this->assertStringContainsString('RouteListCommandTest.php:'.$closureLine, $output);
        $this->assertStringContainsString('Showing [1] routes', $output);
    }

    public function testRouteCanBeFilteredByAction()
    {
        $this->withoutDeprecationHandling();

        RouteListCommand::resolveTerminalWidthUsing(function () {
            return 82;
        });

        $this->router->get('/', function () {
            //
        });
        $this->router->get('foo/{user}', [FooController::class, 'show']);

        $this->artisan(RouteListCommand::class, ['--action' => 'FooController'])
            ->assertSuccessful()
            ->expectsOutput('')
            ->expectsOutput(
                '  GET|HEAD       foo/{user} Illuminate\Tests\Testing\Console\FooController@show'
            )->expectsOutput('')
            ->expectsOutput(
                '                                                              Showing [1] routes'
            )
            ->expectsOutput('');
    }

    public function testClosurePathIsDisplayedInVerboseMode()
    {
        $closureLine = __LINE__ + 1;
        $this->router->get('closure-path', function () {
            //
        });

        $this->router->get('controller-method/{user}', [FooController::class, 'show']);

        $expectedPath = 'tests/Testing/Console/RouteListCommandTest.php:'.$closureLine;

        $this->artisan(RouteListCommand::class, ['-v' => true])
            ->assertSuccessful()
            ->expectsOutputToContain($expectedPath);
    }

    public function testClosurePathIsDisplayedInNonVerboseMode()
    {
        RouteListCommand::resolveTerminalWidthUsing(fn () => 200);

        $closureLine = __LINE__ + 1;
        $this->router->get('closure-path', function () {
            //
        });

        $expectedPath = 'tests/Testing/Console/RouteListCommandTest.php:'.$closureLine;

        $this->artisan(RouteListCommand::class)
            ->assertSuccessful()
            ->expectsOutputToContain($expectedPath);
    }

    public function testClosurePathIsIncludedInJsonOutput()
    {
        $closureLine = __LINE__ + 1;
        $this->router->get('closure-path', function () {
            //
        });

        $this->router->get('controller-method/{user}', [FooController::class, 'show']);

        $expectedPath = 'tests/Testing/Console/RouteListCommandTest.php:'.$closureLine;
        $jsonPath = str_replace('/', '\\/', $expectedPath);

        $this->artisan(RouteListCommand::class, ['--json' => true])
            ->assertSuccessful()
            ->expectsOutputToContain($jsonPath);
    }

    public function testControllerRouteHasNullPathInJsonOutput()
    {
        $this->router->get('controller-method/{user}', [FooController::class, 'show']);

        $this->artisan(RouteListCommand::class, ['--json' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('"path":null');
    }

    public function testDisplayRoutesExceptVendor()
    {
        $this->router->get('foo/{user}', [FooController::class, 'show']);
        $this->router->view('view', 'blade.path');
        $this->router->redirect('redirect', 'destination');

        $this->artisan(RouteListCommand::class, ['-v' => true, '--except-vendor' => true])
            ->assertSuccessful()
            ->expectsOutput('')
            ->expectsOutput('  GET|HEAD       foo/{user} Illuminate\Tests\Testing\Console\FooController@show')
            ->expectsOutput('  ANY            redirect .... Illuminate\Routing\RedirectController')
            ->expectsOutput('  GET|HEAD       view .............................................. ')
            ->expectsOutput('')
            ->expectsOutput('                                                  Showing [3] routes')
            ->expectsOutput('');
    }

    public function testDisplayRoutesWithBindingFields()
    {
        $this->router->get('users/{user:name}', [FooController::class, 'show']);
        $this->router->get('users/{user:name}/posts/{post:slug}', function () {
            //
        });

        $this->artisan(RouteListCommand::class, ['-v' => true])
            ->assertSuccessful()
            ->expectsOutput('')
            ->expectsOutput('  GET|HEAD       users/{user:name} Illuminate\Tests\Testing\Console\FooController@show')
            ->expectsOutput('  GET|HEAD       users/{user:name}/posts/{post:slug} ............... ')
            ->expectsOutput('')
            ->expectsOutput('                                                  Showing [2] routes')
            ->expectsOutput('');
    }

    public function testDisplayRoutesWithBindingFieldsAsJson()
    {
        $this->router->get('users/{user:name}/posts/{post:slug}', function () {
            //
        });

        $this->artisan(RouteListCommand::class, ['--json' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('users\/{user:name}\/posts\/{post:slug}');
    }
}

class FooController extends Controller
{
    public function show(User $user)
    {
        // ..
    }

    public function __invoke()
    {
        // ..
    }
}
