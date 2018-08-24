<?php

namespace Illuminate\Tests\Integration\Routing;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;

class RouteImplicitBindingTest extends TestCase
{
    protected $user;

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [RouteServiceProvider::class];
    }

    protected function setUp()
    {
        parent::setUp();

        $this->loadLaravelMigrations();

        $this->user = User::create([
            'name' => 'Laravel',
            'email' => 'awesome@laravel.com',
            'password' => 'vault',
        ]);
    }

    /**
     * @test
     */
    public function implicit_route_binding()
    {
        Route::middleware(SubstituteBindings::class)->get('/users/{user}', function (User $user) {
            return $user;
        });

        $this->get("/users/{$this->user->id}")
            ->assertSuccessful()
            ->assertJsonFragment([
                'id' => $this->user->id,
                'email' => 'awesome@laravel.com',
                'password' => 'vault'
            ]);
    }

    /**
     * @test
     */
    public function invalid_id_gives_404()
    {
        Route::middleware(SubstituteBindings::class)->get('/users/{user}', function (User $user) {
            return $user;
        });

        $this->get('/users/999')
            ->assertStatus(404);
    }

    /**
     * @test
     */
    public function snake_case_works_for_camel_case_model_classes()
    {
        Route::middleware(SubstituteBindings::class)->get('/{snake_guest_user}', function (GuestUser $snakeGuestUser) {
            return $snakeGuestUser;
        });

        $this->get('/1')
            ->assertSuccessful()
            ->assertJsonFragment([
                'id' => $this->user->id,
                'email' => 'awesome@laravel.com',
                'password' => 'vault'
            ]);
    }

    /**
     * @test
     */
    public function substitute_bindings_must_have_a_valid_route_binding_name()
    {
        Route::middleware(SubstituteBindings::class)->get('/{invalid}', function (User $user) {
            return $user;
        });

        // This test proves that when the Route Variable name {invalid} does not match the variable name {user},
        // it will not load the record. It will, however, instantiate a new Eloquent Object and run the route
        // normally. Is this th expected behavior?
        $this->get('/1')
            ->assertSuccessful()
            ->assertJson([]);
    }
}

class User extends Model
{
    protected $guarded = [];
}

class GuestUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];
}
