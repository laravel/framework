<?php

namespace Illuminate\Tests\Integration\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

#[WithMigration]
#[WithEnv('BCRYPT_ROUNDS', 12)]
#[WithConfig('app.key', 'base64:IUHRqAQ99pZ0A1MPjbuv1D6ff3jxv0GIvS2qIW4JNU4=')]
class RehashOnLogoutOtherDevicesTest extends TestCase
{
    use RefreshDatabase;

    protected function defineRoutes($router)
    {
        $router->post('login', function (Request $request) {
            abort_unless(auth()->attempt($request->only('email', 'password'), $request->boolean('remember')), 401);

            if ($request->boolean('logout_other_devices')) {
                auth()->logoutOtherDevices($request->input('password'));
            }

            return redirect('protected');
        })->middleware(['web', 'auth.session'])->name('login');

        $router->get('protected', function () {
            return response()->noContent();
        })->middleware(['web', 'auth', 'auth.session']);

        $router->post('logout', function (Request $request) {
            auth()->logoutOtherDevices($request->input('password'));

            return response()->noContent();
        })->middleware(['web', 'auth']);
    }

    public function testItRehashThePasswordUsingLogoutOtherDevices()
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create();

        $password = $user->password;

        $this->actingAs($user);

        $this->post('logout', [
            'password' => 'password',
        ])->assertStatus(204);

        $user->refresh();

        $this->assertNotSame($password, $user->password);
    }

    #[DataProvider('guardAndRememberProvider')]
    public function testLoggingOutOtherDevicesAfterLoginPreservesCurrentSession($guard, $remember)
    {
        $this->app['config']->set('auth.guards.admin', $this->app['config']->get('auth.guards.web'));
        $this->app['auth']->shouldUse($guard);

        $user = UserFactory::new()->create();
        $credentials = ['email' => $user->email, 'password' => 'password'];

        $this->post('login', $credentials)->assertRedirect('protected');

        $session = $this->app['session']->driver();
        $otherSessionId = $session->getId();
        $passwordHash = $user->fresh()->getAuthPassword();

        $session->flush();
        $this->app['auth']->forgetGuards();

        $this->post('login', $credentials + [
            'remember' => $remember,
            'logout_other_devices' => true,
        ])->assertRedirect('protected');

        $this->assertNotSame($passwordHash, $user->fresh()->getAuthPassword());

        $this->app['auth']->forgetGuards();

        $this->withCookie($session->getName(), $session->getId())
            ->get('protected')->assertNoContent();

        $session->flush();
        $this->app['auth']->forgetGuards();

        $this->withCookie($session->getName(), $otherSessionId)
            ->withCredentials()->getJson('protected')->assertUnauthorized();
    }

    public static function guardAndRememberProvider()
    {
        return [
            'web' => ['web', false],
            'web with remember me' => ['web', true],
            'admin' => ['admin', false],
            'admin with remember me' => ['admin', true],
        ];
    }
}
