<?php

namespace Illuminate\Tests\Integration\Auth\Middleware;

use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Tests\Integration\Auth\Fixtures\Models\AuthenticationTestUser;
use Orchestra\Testbench\Factories\UserFactory;
use Orchestra\Testbench\TestCase;

class RequirePasswordTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();

        UserFactory::new()->create();

        $this->user = AuthenticationTestUser::first();
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', Str::random(32));
        $app['config']->set('auth.providers.users.model', AuthenticationTestUser::class);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
    }

    public function testItCanGenerateDefinitionViaStaticMethod()
    {
        $signature = (string) RequirePassword::using('route.name');
        $this->assertSame('Illuminate\Auth\Middleware\RequirePassword:route.name', $signature);

        $signature = (string) RequirePassword::using('route.name', 100);
        $this->assertSame('Illuminate\Auth\Middleware\RequirePassword:route.name,100', $signature);

        $signature = (string) RequirePassword::using(passwordTimeoutSeconds: 100);
        $this->assertSame('Illuminate\Auth\Middleware\RequirePassword:,100', $signature);
    }

    public function testUserSeesTheWantedPageIfThePasswordWasRecentlyConfirmed()
    {
        $this->withoutExceptionHandling();

        /** @var \Illuminate\Contracts\Routing\Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('test-route', function (): Response {
            return new Response('foobar');
        })->middleware([RequirePassword::class]);

        $identifier = $this->user->getUniqueIdentifierForUser();
        cache(["auth.password_confirmed_at.$identifier" => time()]);
        $response = $this->actingAs($this->user)->get('test-route');

        $response->assertOk();
        $response->assertSeeText('foobar');
    }

    public function testUserIsRedirectedToThePasswordConfirmRouteIfThePasswordWasNotRecentlyConfirmed()
    {
        $this->withoutExceptionHandling();

        /** @var \Illuminate\Contracts\Routing\Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('password-confirm', function (): Response {
            return new Response('foo');
        })->name('password.confirm');

        $router->get('test-route', function (): Response {
            return new Response('foobar');
        })->middleware([RequirePassword::class]);

        $identifier = $this->user->getUniqueIdentifierForUser();
        cache(["auth.password_confirmed_at.$identifier" => time() - 10801]);

        $response = $this->actingAs($this->user)->get('test-route');

        $response->assertStatus(302);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('password.confirm'));
    }

    public function testUserIsRedirectedToACustomRouteIfThePasswordWasNotRecentlyConfirmedAndTheCustomRouteIsSpecified()
    {
        $this->withoutExceptionHandling();

        /** @var \Illuminate\Contracts\Routing\Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('confirm', function (): Response {
            return new Response('foo');
        })->name('my-password.confirm');

        $router->get('test-route', function (): Response {
            return new Response('foobar');
        })->middleware([RequirePassword::class.':my-password.confirm']);

        $identifier = $this->user->getUniqueIdentifierForUser();
        cache(["auth.password_confirmed_at.$identifier" => time() - 10801]);
        $response = $this->actingAs($this->user)->get('test-route');

        $response->assertStatus(302);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('my-password.confirm'));
    }

    public function testAuthPasswordTimeoutIsConfigurable()
    {
        $this->withoutExceptionHandling();

        /** @var \Illuminate\Contracts\Routing\Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('password-confirm', function (): Response {
            return new Response('foo');
        })->name('password.confirm');

        $router->get('test-route', function (): Response {
            return new Response('foobar');
        })->middleware([RequirePassword::class]);

        $this->app->make(Repository::class)->set('auth.password_timeout', 500);

        $identifier = $this->user->getUniqueIdentifierForUser();
        cache(["auth.password_confirmed_at.$identifier" => time() - 495]);
        $response = $this->actingAs($this->user)->get('test-route');

        $response->assertOk();
        $response->assertSeeText('foobar');

        cache(["auth.password_confirmed_at.$identifier" => time() - 501]);
        $response = $this->actingAs($this->user)->get('test-route');

        $response->assertStatus(302);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('password.confirm'));
    }

    protected function tearDown(): void
    {
        $identifier = $this->user->getUniqueIdentifierForUser();
        cache()->forget("auth.password_confirmed_at.$identifier");

        parent::tearDown();
    }
}