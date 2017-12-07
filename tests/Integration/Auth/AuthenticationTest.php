<?php

namespace Illuminate\Tests\Integration\Auth;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Foundation\Auth\User as Authenticatable;

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
    }

    public function setUp()
    {
        parent::setUp();

        Schema::create('users', function ($table) {
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

    /**
     * @test
     */
    public function basic_auth_protects_route()
    {
        $this->get('basic')->assertStatus(401);
    }

    /**
     * @test
     */
    public function basic_auth_passes_on_correct_credentials()
    {
        $response = $this->get('basic', [
            'Authorization' => 'Basic '.base64_encode('email:password'),
        ]);

        $response->assertStatus(200);
        $this->assertEquals('email', $response->decodeResponseJson()['email']);
    }

    /**
     * @test
     */
    public function basic_auth_respects_additional_conditions()
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

    /**
     * @test
     */
    public function basic_auth_fails_on_wrong_credentials()
    {
        $this->get('basic', [
            'Authorization' => 'Basic '.base64_encode('email:wrong_password'),
        ])->assertStatus(401);
    }

    /**
     * @test
     */
    public function logging_in_via_attempt()
    {
        Event::fake();

        $this->assertFalse(
            $this->app['auth']->attempt(['email' => 'wrong', 'password' => 'password'])
        );
        $this->assertFalse($this->app['auth']->check());
        $this->assertNull($this->app['auth']->user());
        Event::assertDispatched(\Illuminate\Auth\Events\Failed::class);

        $this->assertTrue(
            $this->app['auth']->attempt(['email' => 'email', 'password' => 'password'])
        );
        $this->assertInstanceOf(AuthenticationTestUser::class, $this->app['auth']->user());
        $this->assertTrue($this->app['auth']->check());

        Event::assertDispatched(\Illuminate\Auth\Events\Login::class);
        Event::assertDispatched(\Illuminate\Auth\Events\Authenticated::class);
    }

    /**
     * @test
     */
    public function test_logging_in_using_id()
    {
        $this->app['auth']->loginUsingId(1);
        $this->assertEquals(1, $this->app['auth']->user()->id);

        $this->assertFalse($this->app['auth']->loginUsingId(1000));
    }

    /**
     * @test
     */
    public function test_logging_out()
    {
        Event::fake();

        $this->app['auth']->loginUsingId(1);
        $this->assertEquals(1, $this->app['auth']->user()->id);

        $this->app['auth']->logout();
        $this->assertNull($this->app['auth']->user());
        Event::assertDispatched(\Illuminate\Auth\Events\Logout::class);
    }

    /**
     * @test
     */
    public function logging_in_out_via_attempt_remembering()
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

    /**
     * @test
     */
    public function auth_via_attempt_remembering()
    {
        $provider = new EloquentUserProvider(app('hash'), AuthenticationTestUser::class);

        $user = AuthenticationTestUser::create([
            'username' => 'username2',
            'email' => 'email2',
            'password' => bcrypt('password'),
            'remember_token' => $token = str_random(),
            'is_active' => false,
        ]);

        $this->assertEquals($user->id, $provider->retrieveByToken($user->id, $token)->id);

        $user->update([
            'remember_token' => null,
        ]);

        $this->assertNull($provider->retrieveByToken($user->id, $token));
    }
}

class AuthenticationTestUser extends Authenticatable
{
    public $table = 'users';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
}
