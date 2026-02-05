<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\ArrayBackedJsonApiResource;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\Post;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\User;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\UserResource;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\UserWithArrayRelationshipResource;
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

        $router->get('users/{userId}/with-array-relationship', function ($userId) {
            $resource = new UserWithArrayRelationshipResource(User::find($userId));
            $resource->loadedRelationshipsMap = [
                [new ArrayBackedJsonApiResource(['id' => 99, 'name' => 'test']), 'things', '99', true],
            ];

            return $resource;
        });

        $router->get('users/{userId}/with-duplicate-instances', function ($userId) {
            $instance1 = User::find($userId);
            $instance2 = User::find($userId);

            $resource = new UserWithArrayRelationshipResource(User::find($userId));
            $resource->loadedRelationshipsMap = [
                [new UserResource($instance1), 'users', (string) $instance1->getKey(), true],
                [new UserResource($instance2), 'users', (string) $instance2->getKey(), true],
            ];

            return $resource;
        });
    }

    /** {@inheritdoc} */
    protected function afterRefreshingDatabase()
    {
        require __DIR__.'/Fixtures/migrations.php';
    }
}
