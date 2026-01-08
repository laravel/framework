<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\Post;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\User;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithConfig('auth.providers.users.model', User::class)]
abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use LazilyRefreshDatabase;

    /** {@inheritdoc} */
    #[\Override]
    protected function setUp(): void
    {
        Model::shouldBeStrict(true);

        parent::setUp();
    }

    /** {@inheritdoc} */
    #[\Override]
    protected function tearDown(): void
    {
        JsonResource::flushState();
        JsonApiResource::flushState();

        parent::tearDown();
    }

    /** {@inheritdoc} */
    #[\Override]
    protected function defineRoutes($router)
    {
        $router->get('users', function () {
            return User::paginate(5)->toResourceCollection();
        });

        $router->get('users/{userId}', function ($userId) {
            return User::find($userId)->toResource();
        });

        $router->get('users/{userId}/with-chaperone-posts', function ($userId) {
            return User::find($userId)->load('chaperonePosts')->toResource();
        });

        $router->get('posts', function () {
            return Post::paginate(5)->toResourceCollection();
        });

        $router->get('posts/{postId}', function ($postId) {
            return Post::find($postId)->toResource();
        });
    }

    /** {@inheritdoc} */
    protected function afterRefreshingDatabase()
    {
        require __DIR__.'/Fixtures/migrations.php';
    }
}
