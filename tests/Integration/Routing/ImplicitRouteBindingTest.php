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

    protected function tearDown(): void
    {
        $this->tearDownInteractsWithPublishedFiles();

        parent::tearDown();
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->timestamps();
        });

        $this->beforeApplicationDestroyed(function () {
            Schema::dropIfExists('users');
            Schema::dropIfExists('posts');
        });
    }

    public function testWithRouteCachingEnabled()
    {
        $this->defineCacheRoutes(<<<PHP
<?php

use Illuminate\Tests\Integration\Routing\ImplicitBindingUser;

Route::post('/user/{user}', function (ImplicitBindingUser \$user) {
    return \$user;
})->middleware('web');
PHP);

        $user = ImplicitBindingUser::create(['name' => 'Dries']);

        $response = $this->postJson("/user/{$user->id}");

        $response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
        ]);
    }

    public function testWithoutRouteCachingEnabled()
    {
        $user = ImplicitBindingUser::create(['name' => 'Dries']);

        config(['app.key' => str_repeat('a', 32)]);

        Route::post('/user/{user}', function (ImplicitBindingUser $user) {
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
        $user = ImplicitBindingUser::create(['name' => 'Dries']);

        $user->delete();

        config(['app.key' => str_repeat('a', 32)]);

        Route::post('/user/{user}', function (ImplicitBindingUser $user) {
            return $user;
        })->middleware(['web']);

        $response = $this->postJson("/user/{$user->id}");

        $response->assertStatus(404);
    }

    public function testSoftDeletedModelsCanBeRetrievedUsingWithTrashedMethod()
    {
        $user = ImplicitBindingUser::create(['name' => 'Dries']);

        $user->delete();

        config(['app.key' => str_repeat('a', 32)]);

        Route::post('/user/{user}', function (ImplicitBindingUser $user) {
            return $user;
        })->middleware(['web'])->withTrashed();

        $response = $this->postJson("/user/{$user->id}");

        $response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
        ]);
    }

    public function testEnforceScopingImplicitRouteBindings()
    {
        $user = ImplicitBindingUser::create(['name' => 'Dries']);
        $post = ImplicitBindingPost::create(['user_id' => 2]);
        $this->assertEmpty($user->posts);

        config(['app.key' => str_repeat('a', 32)]);

        Route::scopeBindings()->group(function () {
            Route::get('/user/{user}/post/{post}', function (ImplicitBindingUser $user, ImplicitBindingPost $post) {
                return [$user, $post];
            })->middleware(['web']);
        });

        $response = $this->getJson("/user/{$user->id}/post/{$post->id}");

        $response->assertNotFound();
    }

    public function testEnforceScopingImplicitRouteBindingsWithTrashedAndChildWithNoSoftDeleteTrait()
    {
        $user = ImplicitBindingUser::create(['name' => 'Dries']);

        $post = $user->posts()->create();

        $user->delete();

        config(['app.key' => str_repeat('a', 32)]);
        Route::scopeBindings()->group(function () {
            Route::get('/user/{user}/post/{post}', function (ImplicitBindingUser $user, ImplicitBindingPost $post) {
                return [$user, $post];
            })->middleware(['web'])->withTrashed();
        });

        $response = $this->getJson("/user/{$user->id}/post/{$post->id}");
        $response->assertOk();
        $response->assertJson([
            [
                'id' => $user->id,
                'name' => $user->name,
            ],
            [
                'id' => 1,
                'user_id' => 1,
            ],
        ]);
    }

    public function testEnforceScopingImplicitRouteBindingsWithRouteCachingEnabled()
    {
        $user = ImplicitBindingUser::create(['name' => 'Dries']);
        $post = ImplicitBindingPost::create(['user_id' => 2]);
        $this->assertEmpty($user->posts);

        $this->defineCacheRoutes(<<<PHP
<?php

use Illuminate\Tests\Integration\Routing\ImplicitBindingUser;
use Illuminate\Tests\Integration\Routing\ImplicitBindingPost;

Route::group(['scoping' => true], function () {
    Route::get('/user/{user}/post/{post}', function (ImplicitBindingUser \$user, ImplicitBindingPost \$post) {
        return [\$user, \$post];
    })->middleware(['web']);
});
PHP);

        $response = $this->getJson("/user/{$user->id}/post/{$post->id}");

        $response->assertNotFound();
    }

    public function testWithoutEnforceScopingImplicitRouteBindings()
    {
        $user = ImplicitBindingUser::create(['name' => 'Dries']);
        $post = ImplicitBindingPost::create(['user_id' => 2]);
        $this->assertEmpty($user->posts);

        config(['app.key' => str_repeat('a', 32)]);

        Route::group(['scoping' => false], function () {
            Route::get('/user/{user}/post/{post}', function (ImplicitBindingUser $user, ImplicitBindingPost $post) {
                return [$user, $post];
            })->middleware(['web']);
        });

        $response = $this->getJson("/user/{$user->id}/post/{$post->id}");
        $response->assertOk();
        $response->assertJson([
            [
                'id' => $user->id,
                'name' => $user->name,
            ],
            [
                'id' => 1,
                'user_id' => 2,
            ],
        ]);
    }
}

class ImplicitBindingUser extends Model
{
    use SoftDeletes;

    public $table = 'users';

    protected $fillable = ['name'];

    public function posts()
    {
        return $this->hasMany(ImplicitBindingPost::class, 'user_id');
    }
}

class ImplicitBindingPost extends Model
{
    public $table = 'posts';

    protected $fillable = ['user_id'];
}
