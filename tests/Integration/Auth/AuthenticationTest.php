<?php

namespace Illuminate\Tests\Integration\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Auth\Events\Validated;
use Illuminate\Auth\SessionGuard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Testing\Fakes\EventFake;
use Illuminate\Tests\Integration\Auth\Fixtures\AuthenticationTestUser;
use InvalidArgumentException;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;

#[WithMigration]
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app)
    {
        $app['config']->set([
            'auth.providers.users.model' => AuthenticationTestUser::class,
            'hashing.driver' => 'bcrypt',
        ]);
    }

    protected function defineRoutes($router)
    {
        $router->get('basic', function () {
            return $this->app['auth']->guard()->basic()
                ?: $this->app['auth']->user()->toJson();
        });

        $router->get('basicWithCondition', function () {
            return $this->app['auth']->guard()->basic('email', ['is_active' => true])
                ?: $this->app['auth']->user()->toJson();
        });
    }

    protected function afterRefreshingDatabase()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('name', 'username');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('is_active')->default(0);
        });

        AuthenticationTestUser::create([
            'username' => 'username',
            'email' => 'email',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
    }

    public function testBasicAuthProtectsRoute()
    {
        $this->get('basic')->assertStatus(401);
    }

    public function testBasicAuthPassesOnCorrectCredentials()
    {
        $response = $this->get('basic', [
            'Authorization' => 'Basic '.base64_encode('email:password'),
        ]);

        $response->assertStatus(200);
        $this->assertSame('email', $response->json()['email']);
    }

    public function testBasicAuthRespectsAdditionalConditions()
    {
        AuthenticationTestUser::create([
            'username' => 'username2',
            'email' => 'email2',
            'password' => bcrypt('password'),
            'is_active' => false,
        ]);

        $this->get('basicWithCondition', [
            'Authorization' => 'Basic '.base64_encode('email2:password2'),
        ])->assertStatus(401);

        $this->get('basicWithCondition', [
            'Authorization' => 'Basic '.base64_encode('email:password'),
        ])->assertStatus(200);
    }

    public function testBasicAuthFailsOnWrongCredentials()
    {
        $this->get('basic', [
            'Authorization' => 'Basic '.base64_encode('email:wrong_password'),
        ])->assertStatus(401);
    }

    public function testLoggingInFailsViaAttempt()
    {
        Event::fake();

        $this->assertFalse(
            $this->app['auth']->attempt(['email' => 'wrong', 'password' => 'password'])
        );

        $this->assertFalse($this->app['auth']->check());
        $this->assertNull($this->app['auth']->user());

        Event::assertDispatched(Attempting::class, function ($event) {
            $this->assertSame('web', $event->guard);
            $this->assertEquals(['email' => 'wrong', 'password' => 'password'], $event->credentials);

            return true;
        });
        Event::assertNotDispatched(Validated::class);

        Event::assertDispatched(Failed::class, function ($event) {
            $this->assertSame('web', $event->guard);
            $this->assertEquals(['email' => 'wrong', 'password' => 'password'], $event->credentials);
            $this->assertNull($event->user);

            return true;
        });
    }

    public function testLoggingInSucceedsViaAttempt()
    {
        Event::fake();

        $this->assertTrue(
            $this->app['auth']->attempt(['email' => 'email', 'password' => 'password'])
        );
        $this->assertInstanceOf(AuthenticationTestUser::class, $this->app['auth']->user());
        $this->assertTrue($this->app['auth']->check());

        Event::assertDispatched(Attempting::class, function ($event) {
            $this->assertSame('web', $event->guard);
            $this->assertEquals(['email' => 'email', 'password' => 'password'], $event->credentials);

            return true;
        });
        Event::assertDispatched(Validated::class, function ($event) {
            $this->assertSame('web', $event->guard);
            $this->assertEquals(1, $event->user->id);

            return true;
        });
        Event::assertDispatched(Login::class, function ($event) {
            $this->assertSame('web', $event->guard);
            $this->assertEquals(1, $event->user->id);

            return true;
        });
        Event::assertDispatched(Authenticated::class, function ($event) {
            $this->assertSame('web', $event->guard);
            $this->assertEquals(1, $event->user->id);

            return true;
        });
    }

    public function testLoggingInUsingId()
    {
        $this->app['auth']->loginUsingId(1);
        $this->assertEquals(1, $this->app['auth']->user()->id);

        $this->assertFalse($this->app['auth']->loginUsingId(1000));
    }

    public function testLoggingOut()
    {
        Event::fake();

        $this->app['auth']->loginUsingId(1);
        $this->assertEquals(1, $this->app['auth']->user()->id);

        $this->app['auth']->logout();
        $this->assertNull($this->app['auth']->user());
        Event::assertDispatched(Logout::class, function ($event) {
            $this->assertSame('web', $event->guard);
            $this->assertEquals(1, $event->user->id);

            return true;
        });
    }

    public function testLoggingOutOtherDevices()
    {
        Event::fake();

        $this->app['auth']->loginUsingId(1);

        $user = $this->app['auth']->user();

        $this->assertEquals(1, $user->id);

        $this->app['auth']->logoutOtherDevices('password');
        $this->assertEquals(1, $user->id);

        Event::assertDispatched(OtherDeviceLogout::class, function ($event) {
            $this->assertSame('web', $event->guard);
            $this->assertEquals(1, $event->user->id);

            return true;
        });
    }

    public function testPasswordMustBeValidToLogOutOtherDevices()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('current password');

        $this->app['auth']->loginUsingId(1);

        $user = $this->app['auth']->user();

        $this->assertEquals(1, $user->id);

        $this->app['auth']->logoutOtherDevices('adifferentpassword');
    }

    public function testLoggingInOutViaAttemptRemembering()
    {
        $this->assertTrue(
            $this->app['auth']->attempt(['email' => 'email', 'password' => 'password'], true)
        );
        $this->assertInstanceOf(AuthenticationTestUser::class, $this->app['auth']->user());
        $this->assertTrue($this->app['auth']->check());
        $this->assertNotNull($this->app['auth']->user()->getRememberToken());

        $oldToken = $this->app['auth']->user()->getRememberToken();
        $user = $this->app['auth']->user();

        $this->app['auth']->logout();

        $this->assertNotNull($user->getRememberToken());
        $this->assertNotEquals($oldToken, $user->getRememberToken());
    }

    public function testLoggingInOutCurrentDeviceViaRemembering()
    {
        $this->assertTrue(
            $this->app['auth']->attempt(['email' => 'email', 'password' => 'password'], true)
        );
        $this->assertInstanceOf(AuthenticationTestUser::class, $this->app['auth']->user());
        $this->assertTrue($this->app['auth']->check());
        $this->assertNotNull($this->app['auth']->user()->getRememberToken());

        $oldToken = $this->app['auth']->user()->getRememberToken();
        $user = $this->app['auth']->user();

        $this->app['auth']->logoutCurrentDevice();

        $this->assertNotNull($user->getRememberToken());
        $this->assertEquals($oldToken, $user->getRememberToken());
    }

    public function testAuthViaAttemptRemembering()
    {
        $provider = new EloquentUserProvider(app('hash'), AuthenticationTestUser::class);

        $user = AuthenticationTestUser::create([
            'username' => 'username2',
            'email' => 'email2',
            'password' => bcrypt('password'),
            'remember_token' => $token = Str::random(),
            'is_active' => false,
        ]);

        $this->assertEquals($user->id, $provider->retrieveByToken($user->id, $token)->id);

        $user->update([
            'remember_token' => null,
        ]);

        $this->assertNull($provider->retrieveByToken($user->id, $token));
    }

    public function testDispatcherChangesIfThereIsOneOnTheAuthGuard()
    {
        $this->assertInstanceOf(SessionGuard::class, $this->app['auth']->guard());
        $this->assertInstanceOf(Dispatcher::class, $this->app['auth']->guard()->getDispatcher());

        Event::fake();

        $this->assertInstanceOf(SessionGuard::class, $this->app['auth']->guard());
        $this->assertInstanceOf(EventFake::class, $this->app['auth']->guard()->getDispatcher());
    }

    public function testDispatcherChangesIfThereIsOneOnTheCustomAuthGuard()
    {
        $this->app['config']['auth.guards.myGuard'] = [
            'driver' => 'myCustomDriver',
            'provider' => 'user',
        ];

        Auth::extend('myCustomDriver', function () {
            return new MyCustomGuardStub;
        });

        $this->assertInstanceOf(MyCustomGuardStub::class, $this->app['auth']->guard('myGuard'));
        $this->assertInstanceOf(Dispatcher::class, $this->app['auth']->guard()->getDispatcher());

        Event::fake();

        $this->assertInstanceOf(MyCustomGuardStub::class, $this->app['auth']->guard('myGuard'));
        $this->assertInstanceOf(EventFake::class, $this->app['auth']->guard()->getDispatcher());
    }

    public function testHasNoProblemIfThereIsNoDispatchingTheAuthCustomGuard()
    {
        $this->app['config']['auth.guards.myGuard'] = [
            'driver' => 'myCustomDriver',
            'provider' => 'user',
        ];

        Auth::extend('myCustomDriver', function () {
            return new MyDispatcherLessCustomGuardStub;
        });

        $this->assertInstanceOf(MyDispatcherLessCustomGuardStub::class, $this->app['auth']->guard('myGuard'));

        Event::fake();

        $this->assertInstanceOf(MyDispatcherLessCustomGuardStub::class, $this->app['auth']->guard('myGuard'));
    }
}

class MyCustomGuardStub
{
    protected $events;

    public function __construct()
    {
        $this->setDispatcher(new Dispatcher);
    }

    public function setDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }

    public function getDispatcher()
    {
        return $this->events;
    }
}

class MyDispatcherLessCustomGuardStub
{
    //
}
