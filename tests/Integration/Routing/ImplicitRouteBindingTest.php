<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Orchestra\Testbench\TestCase;

class ImplicitRouteBindingTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected $files = [
        'routes/testbench.php',
    ];

    /**
     * Teardown the test environment.
     */
    protected function tearDown(): void
    {
        $this->tearDownInteractsWithPublishedFiles();

        parent::tearDown();
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('logins', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('user_id');
            $table->timestamps();
        });

        $this->beforeApplicationDestroyed(function () {
            Schema::dropIfExists('users');
            Schema::dropIfExists('logins');
        });
    }

    public function testWithRouteCachingEnabled()
    {
        $this->defineCacheRoutes(<<<PHP
<?php

use Illuminate\Tests\Integration\Routing\ImplicitBindingModel;

Route::post('/user/{user}', function (ImplicitBindingModel \$user) {
    return \$user;
})->middleware('web');
PHP);

        $user = ImplicitBindingModel::create(['name' => 'Dries']);

        $response = $this->postJson("/user/{$user->id}");

        $response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
        ]);
    }

    public function testWithoutRouteCachingEnabled()
    {
        $user = ImplicitBindingModel::create(['name' => 'Dries']);

        config(['app.key' => str_repeat('a', 32)]);

        Route::post('/user/{user}', function (ImplicitBindingModel $user) {
            return $user;
        })->middleware(['web']);

        $response = $this->postJson("/user/{$user->id}");

        $response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
        ]);
    }

    public function testSoftDeletedModelsAreNotRetrieved()
    {
        $user = ImplicitBindingModel::create(['name' => 'Dries']);

        $user->delete();

        config(['app.key' => str_repeat('a', 32)]);

        Route::post('/user/{user}', function (ImplicitBindingModel $user) {
            return $user;
        })->middleware(['web']);

        $response = $this->postJson("/user/{$user->id}");

        $response->assertStatus(404);
    }

    public function testSoftDeletedModelsCanBeRetrievedUsingWithTrashedMethod()
    {
        $user = ImplicitBindingModel::create(['name' => 'Dries']);

        $user->delete();

        config(['app.key' => str_repeat('a', 32)]);

        Route::post('/user/{user}', function (ImplicitBindingModel $user) {
            return $user;
        })->middleware(['web'])->withTrashed();

        $response = $this->postJson("/user/{$user->id}");

        $response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
        ]);
    }

    public function testRoutesRelationshipsAreLoadedWhenRoutePassedAString()
    {
        $user = ImplicitBindingModel::create(['name' => 'Dries']);
        $login = $user->logins()->create();

        Route::post('/user/{user}', function (ImplicitBindingModel $user) {
            return $user;
        })->middleware(['web'])->with('logins');

        $response = $this->postJson("/user/{$user->id}");

        $response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
            'logins' => [
                [
                    'id' => $login->id,
                ]
            ]
        ]);
    }

    public function testRoutesRelationshipsAreLoadedWhenPassedAStringWithRouteCachingEnabled()
    {
        $this->defineCacheRoutes(<<<PHP
<?php

use Illuminate\Tests\Integration\Routing\ImplicitBindingModel;

Route::post('/user/{user}', function (ImplicitBindingModel \$user) {
    return \$user;
})->middleware('web')->with('logins');
PHP);

        $user = ImplicitBindingModel::create(['name' => 'Dries']);
        $login = $user->logins()->create();

        $response = $this->postJson("/user/{$user->id}");

        $response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
            'logins' => [
                [
                    'id' => $login->id,
                ]
            ]
        ]);
    }

    public function testRoutesRelationshipsAreLoadedWhenRoutePassedAnArray()
    {
        $user = ImplicitBindingModel::create(['name' => 'Dries']);
        $login = $user->logins()->create();

        Route::post('/user/{user}', function (ImplicitBindingModel $user) {
            return $user;
        })->middleware(['web'])->with(['logins']);

        $response = $this->postJson("/user/{$user->id}");

        $response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
            'logins' => [
                [
                    'id' => $login->id,
                ]
            ]
        ]);
    }

    public function testRoutesRelationshipsAreLoadedWhenPassedAnArrayWithRouteCachingEnabled()
    {
        $this->defineCacheRoutes(<<<PHP
<?php

use Illuminate\Tests\Integration\Routing\ImplicitBindingModel;

Route::post('/user/{user}', function (ImplicitBindingModel \$user) {
    return \$user;
})->middleware('web')->with(['logins']);
PHP);

        $user = ImplicitBindingModel::create(['name' => 'Dries']);
        $login = $user->logins()->create();

        $response = $this->postJson("/user/{$user->id}");

        $response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
            'logins' => [
                [
                    'id' => $login->id,
                ]
            ]
        ]);
    }
}

class ImplicitBindingModel extends Model
{
    use SoftDeletes;

    public $table = 'users';

    protected $fillable = ['name'];

    public function logins()
    {
        return $this->hasMany(ImplicitBindingLoginModel::class, 'user_id', 'id');
    }
}

class ImplicitBindingLoginModel extends Model
{
    public $table = 'logins';
}
