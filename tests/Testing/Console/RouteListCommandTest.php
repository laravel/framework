<?php

namespace Illuminate\Tests\Testing\Console;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Facade;
use Orchestra\Testbench\TestCase;

class RouteListCommandTest extends TestCase
{
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

        $this->artisan(RouteListCommand::class)
            ->assertSuccessful()
            ->expectsOutput('')
            ->expectsOutput('  GET|HEAD   closure ............................................... ')
            ->expectsOutput('  POST       controller-invokable Illuminate\Tests\Testing\Console\…')
            ->expectsOutput('  GET|HEAD   controller-method/{user} Illuminate\Tests\Testing\Cons…')
            ->expectsOutput('  GET|HEAD   {account}.example.com/user/{id} ............. user.show')
            ->expectsOutput('');
    }

    public function testDisplayRoutesForCliInVerboseMode()
    {
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

        $this->artisan(RouteListCommand::class, ['-v' => true])
            ->assertSuccessful()
            ->expectsOutput('')
            ->expectsOutput('  GET|HEAD   closure ............................................... ')
            ->expectsOutput('  POST       controller-invokable Illuminate\\Tests\\Testing\\Console\\FooController')
            ->expectsOutput('  GET|HEAD   controller-method/{user} Illuminate\\Tests\\Testing\\Console\\FooController@show')
            ->expectsOutput('  GET|HEAD   {account}.example.com/user/{id} ............. user.show')
            ->expectsOutput('             ⇂ web')
            ->expectsOutput('');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Facade::setFacadeApplication(null);

        RouteListCommand::resolveTerminalWidthUsing(null);
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
