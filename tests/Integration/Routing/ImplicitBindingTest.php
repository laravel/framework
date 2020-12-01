<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class ImplicitBindingTest extends TestCase
{
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

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function testPreviousUrlWithoutSession()
    {
        $user = ImplicitBindingModel::create(['name' => 'Dries']);

        Route::post('/user/{user}', function (ImplicitBindingModel $user) {
            dd($user);

            return $user;
        });

        // Artisan::call('route:cache');

        $response = $this->post("/user/{$user->id}");

        $this->assertSame($user->toJson(), $response->content());

        Artisan::call('route:clear');
    }
}

class ImplicitBindingModel extends Model
{
    public $table = 'users';

    protected $fillable = ['name'];
}
