<?php

namespace Illuminate\Tests\Integration\Session;

use BadMethodCallException;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Auth;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;
use Orchestra\Testbench\TestCase;

#[WithMigration]
#[WithConfig('app.key', 'base64:IUHRqAQ99pZ0A1MPjbuv1D6ff3jxv0GIvS2qIW4JNU4=')]
class AuthenticateSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        AuthenticateSession::redirectUsing(fn () => null);

        parent::tearDown();
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('auth.guards.legacy', ['driver' => 'legacy-session', 'provider' => 'users']);
        $app['config']->set('auth.providers.users.model', User::class);

        Auth::extend('legacy-session', fn ($app, $name, $config) => new LegacySessionGuard(
            $name, Auth::createUserProvider($config['provider']), $app['session.store']
        ));

        $app['config']->set('auth.guards.unchecked', ['driver' => 'unchecked-session', 'provider' => 'users']);

        Auth::extend('unchecked-session', function ($app, $name, $config) {
            $guard = new UncheckedRecallerSessionGuard(
                $name, Auth::createUserProvider($config['provider']), $app['session.store']
            );

            $guard->setCookieJar($app['cookie']);
            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }

    protected function defineRoutes($router)
    {
        $router->get('login', fn () => 'login')->name('login');
        $router->get('without-session', fn () => 'ok')->middleware(AuthenticateSession::class);
        $router->get('protected', fn () => 'ok')->middleware(['web', AuthenticateSession::class]);
    }

    public function testItPassesThroughWithoutASession()
    {
        $this->actingAs(UserFactory::new()->create());

        $this->get('without-session')->assertOk();
    }

    public function testItPassesThroughForGuests()
    {
        $this->get('protected')->assertOk()->assertSessionMissing('password_hash_web');
    }

    public function testItPassesThroughForUsersWithoutAPassword()
    {
        $this->actingAs(UserFactory::new()->make(['password' => null]));

        $this->get('protected')->assertOk()->assertSessionMissing('password_hash_web');
    }

    public function testItStoresThePasswordHashInTheSession()
    {
        $user = UserFactory::new()->create();

        $this->actingAs($user)->get('protected')
            ->assertOk()
            ->assertSessionHas('password_hash_web', $this->hashFor($user));
    }

    public function testItKeepsTheSessionWhenThePasswordHashMatches()
    {
        $user = UserFactory::new()->create();

        $this->actingAs($user)
            ->withSession(['password_hash_web' => $this->hashFor($user), 'a' => '1'])
            ->get('protected')
            ->assertOk()
            ->assertSessionHas(['password_hash_web' => $this->hashFor($user), 'a' => '1']);
    }

    public function testItLogsOutWhenThePasswordHashDoesNotMatch()
    {
        $user = UserFactory::new()->create();

        $this->actingAs($user)
            ->withSession(['password_hash_web' => 'invalid-password', 'a' => '1'])
            ->getJson('protected')
            ->assertUnauthorized()
            ->assertSessionMissing('password_hash_web')
            ->assertSessionMissing('a');

        $this->assertGuest();
    }

    public function testItRedirectsUsingTheConfiguredCallback()
    {
        // The default redirect is registered once the HTTP kernel is resolved...
        $this->app->make(Kernel::class);

        AuthenticateSession::redirectUsing(fn () => '/i-wanna-go-home');

        $user = UserFactory::new()->create();

        $this->actingAs($user)
            ->withSession(['password_hash_web' => 'invalid-password'])
            ->get('protected')
            ->assertRedirect('/i-wanna-go-home');
    }

    public function testItAcceptsAValidRememberCookie()
    {
        $user = UserFactory::new()->create();

        $this->withRecaller($user, $this->hashFor($user))
            ->withSession(['password_hash_web' => $this->hashFor($user), 'a' => '1'])
            ->get('protected')
            ->assertOk()
            ->assertSessionHas('a', '1');

        $this->assertTrue(Auth::guard()->viaRemember());
    }

    public function testItLogsOutWhenTheRememberedSessionHashDoesNotMatch()
    {
        $user = UserFactory::new()->create();

        $this->withRecaller($user, $this->hashFor($user))
            ->withSession(['password_hash_web' => 'invalid-password', 'a' => '1'])
            ->get('protected')
            ->assertRedirect(route('login'))
            ->assertSessionMissing('password_hash_web')
            ->assertSessionMissing('a');
    }

    public function testItIgnoresARememberCookieWithAStaleHash()
    {
        $user = UserFactory::new()->create();

        $this->withRecaller($user, $this->hashFor($user).'-stale')
            ->withSession(['a' => '1'])
            ->get('protected')
            ->assertOk()
            ->assertSessionHas('a', '1')
            ->assertSessionMissing('password_hash_web');

        $this->assertGuest();
    }

    public function testItLogsOutWhenACustomGuardRemembersTheUserWithoutCheckingTheCookieHash()
    {
        $user = UserFactory::new()->create();

        $this->app['config']->set('auth.defaults.guard', 'unchecked');

        $name = Auth::guard('unchecked')->getRecallerName();

        // The built-in guard rejects a stale hash itself, but a custom guard might not...
        $this->withCookie($name, "{$user->getAuthIdentifier()}|{$user->getRememberToken()}|stale-hash")
            ->withSession(['a' => '1'])
            ->get('protected')
            ->assertRedirect(route('login'))
            ->assertSessionMissing('a');
    }

    public function testItUpgradesTheOldFormatPasswordHash()
    {
        $user = UserFactory::new()->create();

        // Old cookies and sessions hold the raw password hash instead of its HMAC...
        $this->withRecaller($user, $user->password)
            ->withSession(['password_hash_web' => $user->password, 'a' => '1'])
            ->get('protected')
            ->assertOk()
            ->assertSessionHas(['password_hash_web' => $this->hashFor($user), 'a' => '1']);
    }

    public function testItSupportsGuardsWithoutPasswordHashing()
    {
        $user = UserFactory::new()->create();

        $this->app['config']->set('auth.defaults.guard', 'legacy');

        $this->actingAs($user, 'legacy')
            ->withSession(['password_hash_legacy' => $user->password, 'a' => '1'])
            ->get('protected')
            ->assertOk()
            ->assertSessionHas(['password_hash_legacy' => $user->password, 'a' => '1']);
    }

    protected function hashFor($user)
    {
        return Auth::guard('web')->hashPasswordForCookie($user->password);
    }

    protected function withRecaller($user, $hash)
    {
        $name = Auth::guard('web')->getRecallerName();

        return $this->withCookie($name, "{$user->getAuthIdentifier()}|{$user->getRememberToken()}|{$hash}");
    }
}

class LegacySessionGuard extends SessionGuard
{
    public function hashPasswordForCookie($passwordHash)
    {
        throw new BadMethodCallException;
    }
}

class UncheckedRecallerSessionGuard extends SessionGuard
{
    protected function userFromRecaller($recaller)
    {
        if (! $recaller->valid() || $this->recallAttempted) {
            return;
        }

        $this->recallAttempted = true;

        $this->viaRemember = ! is_null($user = $this->provider->retrieveByToken(
            $recaller->id(), $recaller->token()
        ));

        return $this->viaRemember ? $user : null;
    }
}
