<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Orchestra\Testbench\TestCase;

class ImplicitModelRouteBindingTest extends TestCase
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

        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug');
            $table->integer('user_id');
            $table->timestamps();
        });

        $this->beforeApplicationDestroyed(function () {
            Schema::dropIfExists('users');
            Schema::dropIfExists('posts');
            Schema::dropIfExists('comments');
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

Route::group(['scope_bindings' => true], function () {
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

        Route::group(['scope_bindings' => false], function () {
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

    public function testImplicitRouteBindingChildHasUuids()
    {
        $user = ImplicitBindingUser::create(['name' => 'Dries']);
        $comment = ImplicitBindingComment::create([
            'slug' => 'slug',
            'user_id' => $user->id,
        ]);

        config(['app.key' => str_repeat('a', 32)]);

        $function = function (ImplicitBindingUser $user, ImplicitBindingComment $comment) {
            return [$user, $comment];
        };

        Route::middleware(['web'])->group(function () use ($function) {
            Route::get('/user/{user}/comment/{comment}', $function);
            Route::get('/user/{user}/comment-id/{comment:id}', $function);
            Route::get('/user/{user}/comment-slug/{comment:slug}', $function);
        });

        $response = $this->getJson("/user/{$user->id}/comment/{$comment->slug}");
        $response->assertJsonFragment(['id' => $comment->id]);

        $response = $this->getJson("/user/{$user->id}/comment-id/{$comment->id}");
        $response->assertJsonFragment(['id' => $comment->id]);

        $response = $this->getJson("/user/{$user->id}/comment-slug/{$comment->slug}");
        $response->assertJsonFragment(['id' => $comment->id]);
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

    public function comments()
    {
        return $this->hasMany(ImplicitBindingComment::class, 'user_id');
    }
}

class ImplicitBindingPost extends Model
{
    public $table = 'posts';

    protected $fillable = ['user_id'];
}

class ImplicitBindingComment extends Model
{
    use HasUuids;

    public $table = 'comments';

    protected $fillable = ['slug', 'user_id'];

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
