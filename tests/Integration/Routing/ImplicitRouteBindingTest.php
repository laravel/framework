<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
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
        });

        $this->beforeApplicationDestroyed(function () {
            Schema::dropIfExists('users');
        });
    }

    public function testWithRouteCachingEnabled()
    {
        $route = <<<PHP
<?php

use Illuminate\Tests\Integration\Routing\ImplicitBindingModel;

Route::post('/user/{user}', function (ImplicitBindingModel \$user) {
    return \$user;
})->middleware('web');
PHP;

        file_put_contents(base_path('routes/testbench.php'), $route);

        $this->artisan('route:cache')->run();

        $this->reloadApplication();

        $this->assertFilenameExists('bootstrap/cache/routes-v7.php');

        $this->requireApplicationCachedRoutes();

        $user = ImplicitBindingModel::create(['name' => 'Dries']);

        $response = $this->postJson("/user/{$user->id}");

        $response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
        ]);

        $this->artisan('route:clear')->run();
    }
}

class ImplicitBindingModel extends Model
{
    public $table = 'users';

    protected $fillable = ['name'];
}
