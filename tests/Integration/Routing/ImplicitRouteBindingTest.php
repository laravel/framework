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

        $this->beforeApplicationDestroyed(function () {
            Schema::dropIfExists('users');
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

    public function testSoftDeletedModelsCanBeRetrievedUsingWithTrashedMethodOnGroups()
    {
        $user = ImplicitBindingModel::create(['name' => 'Dries']);

        $user->delete();

        config(['app.key' => str_repeat('a', 32)]);

        Route::group(['prefix' => 'user', 'middleware' => 'web', 'withTrashed' => true], function () {
            Route::post('/{user}', function (ImplicitBindingModel $user) {
                return $user;
            });

            Route::get('/{user}/edit', function (ImplicitBindingModel $user) {
                return $user;
            });

            Route::group(['prefix' => 'users'], function () {
                Route::get('/{user}/edit', function (ImplicitBindingModel $user) {
                    return $user;
                });
            });
        });

        Route::group(['prefix' => 'not-trashed'], function () {
            Route::group(['prefix' => 'users', 'withTrashed' => true], function () {
                Route::get('/{user}/edit', function (ImplicitBindingModel $user) {
                    return $user;
                });
            });
        });

        $post_response = $this->postJson("/user/{$user->id}");

        $post_response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
        ]);

        $edit_response = $this->getJson("/user/{$user->id}/edit");

        $edit_response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
        ]);

        $nested_edit_response = $this->getJson("/user/users/{$user->id}/edit");

        $nested_edit_response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
        ]);

        $not_trashed_nested_edit_response = $this->getJson("/user/users/{$user->id}/edit");

        $not_trashed_nested_edit_response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
        ]);
    }
}

class ImplicitBindingModel extends Model
{
    use SoftDeletes;

    public $table = 'users';

    protected $fillable = ['name'];
}
