<?php

namespace Illuminate\Tests\Integration\Auth;

use Illuminate\Support\Str;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\SessionGuard;
use Illuminate\Events\Dispatcher;
use Orchestra\Testbench\TestCase;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Support\Testing\Fakes\EventFake;
use Illuminate\Tests\Integration\Auth\Fixtures\AuthenticationTestUser;

/**
 * @group integration
 */
class AuthenticationTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');
        $app['config']->set('auth.providers.users.model', AuthenticationTestUser::class);

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('hashing', ['driver' => 'bcrypt']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->string('username');
            $table->string('password');
            $table->string('remember_token')->default(null)->nullable();
            $table->tinyInteger('is_active')->default(0);
        });

        AuthenticationTestUser::create([
            'username' => 'username',
            'email' => 'email',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->app->make('router')->get('basic', function () {
            return $this->app['auth']->guard()->basic()
                ?: $this->app['auth']->user()->toJson();
        });

        $this->app->make('router')->get('basicWithCondition', function () {
            return $this->app['auth']->guard()->basic('email', ['is_active' => true])
                ?: $this->app['auth']->user()->toJson();
        });
    }

    public function test_basic_auth_protects_route()
    {
        $this->get('basic')->assertStatus(401);
    }

    public function test_basic_auth_passes_on_correct_credentials()
    {
        $response = $this->get('basic', [
            'Authorization' => 'Basic '.base64_encode('email:password'),
        ]);

        $response->assertStatus(200);
        $this->assertEquals('email', $response->decodeResponseJson()['email']);
    }

    public function test_basic_auth_respects_additional_conditions()
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

    public function test_basic_auth_fails_on_wrong_credentials()
    {
        $this->get('basic', [
            'Authorization' => 'Basic '.base64_encode('email:wrong_password'),
        ])->assertStatus(401);
    }

    public function test_logging_in_fails_via_attempt()
    {
        Event::fake();

        $this->assertFalse(
            $this->app['auth']->attempt(['email' => 'wrong', 'password' => 'password'])
        );
        $this->assertFalse($this->app['auth']->check());
        $this->assertNull($this->app['auth']->user());
        Event::assertDispatched(Attempting::class, function ($event) {
            $this->assertEquals('web', $event->guard);
            $this->assertEquals(['email' => 'wrong', 'password' => 'password'], $event->credentials);

            return true;
        });
        Event::assertDispatched(Failed::class, function ($event) {
            $this->assertEquals('web', $event->guard);
            $this->assertEquals(['email' => 'wrong', 'password' => 'password'], $event->credentials);
            $this->assertNull($event->user);

            return true;
        });
    }

    public function test_logging_in_succeeds_via_attempt()
    {
        Event::fake();

        $this->assertTrue(
            $this->app['auth']->attempt(['email' => 'email', 'password' => 'password'])
        );
        $this->assertInstanceOf(AuthenticationTestUser::class, $this->app['auth']->user());
        $this->assertTrue($this->app['auth']->check());

        Event::assertDispatched(Attempting::class, function ($event) {
            $this->assertEquals('web', $event->guard);
            $this->assertEquals(['email' => 'email', 'password' => 'password'], $event->credentials);

            return true;
        });
        Event::assertDispatched(Login::class, function ($event) {
            $this->assertEquals('web', $event->guard);
            $this->assertEquals(1, $event->user->id);

            return true;
        });
        Event::assertDispatched(Authenticated::class, function ($event) {
            $this->assertEquals('web', $event->guard);
            $this->assertEquals(1, $event->user->id);

            return true;
        });
    }

    public function test_logging_in_using_id()
    {
        $this->app['auth']->loginUsingId(1);
        $this->assertEquals(1, $this->app['auth']->user()->id);

        $this->assertFalse($this->app['auth']->loginUsingId(1000));
    }

    public function test_logging_out()
    {
        Event::fake();

        $this->app['auth']->loginUsingId(1);
        $this->assertEquals(1, $this->app['auth']->user()->id);

        $this->app['auth']->logout();
        $this->assertNull($this->app['auth']->user());
        Event::assertDispatched(Logout::class, function ($event) {
            $this->assertEquals('web', $event->guard);
            $this->assertEquals(1, $event->user->id);

            return true;
        });
    }

    public function test_logging_out_other_devices()
    {
        Event::fake();

        $this->app['auth']->loginUsingId(1);

        $user = $this->app['auth']->user();

        $this->assertEquals(1, $user->id);

        $this->app['auth']->logoutOtherDevices('adifferentpassword');
        $this->assertEquals(1, $user->id);

        Event::assertDispatched(OtherDeviceLogout::class, function ($event) {
            $this->assertEquals('web', $event->guard);
            $this->assertEquals(1, $event->user->id);

            return true;
        });
    }

    public function test_logging_in_out_via_attempt_remembering()
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

    public function test_auth_via_attempt_remembering()
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

    public function test_dispatcher_changes_if_there_is_one_on_the_auth_guard()
    {
        $this->assertInstanceOf(SessionGuard::class, $this->app['auth']->guard());
        $this->assertInstanceOf(Dispatcher::class, $this->app['auth']->guard()->getDispatcher());

        Event::fake();

        $this->assertInstanceOf(SessionGuard::class, $this->app['auth']->guard());
        $this->assertInstanceOf(EventFake::class, $this->app['auth']->guard()->getDispatcher());
    }

    public function test_dispatcher_changes_if_there_is_one_on_the_custom_auth_guard()
    {
        $this->app['config']['auth.guards.myGuard'] = [
            'driver' => 'myCustomDriver',
            'provider' => 'user',
        ];

        Auth::extend('myCustomDriver', function () {
            return new MyCustomGuardStub();
        });

        $this->assertInstanceOf(MyCustomGuardStub::class, $this->app['auth']->guard('myGuard'));
        $this->assertInstanceOf(Dispatcher::class, $this->app['auth']->guard()->getDispatcher());

        Event::fake();

        $this->assertInstanceOf(MyCustomGuardStub::class, $this->app['auth']->guard('myGuard'));
        $this->assertInstanceOf(EventFake::class, $this->app['auth']->guard()->getDispatcher());
    }

    public function test_has_no_problem_if_there_is_no_dispatching_the_auth_custom_guard()
    {
        $this->app['config']['auth.guards.myGuard'] = [
            'driver' => 'myCustomDriver',
            'provider' => 'user',
        ];

        Auth::extend('myCustomDriver', function () {
            return new MyDispatcherLessCustomGuardStub();
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
        $this->setDispatcher(new Dispatcher());
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
}
