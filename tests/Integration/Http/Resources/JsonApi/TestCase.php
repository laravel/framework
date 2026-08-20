<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Tests\App\Http\Resources\JsonApi\ArrayBackedJsonApiResource;
use Illuminate\Tests\App\Http\Resources\JsonApi\UserResource;
use Illuminate\Tests\App\Http\Resources\JsonApi\UserWithArrayRelationshipResource;
use Illuminate\Tests\App\Models\JsonApi\ApiPost;
use Illuminate\Tests\App\Models\JsonApi\ApiUser;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithConfig('auth.providers.users.model', ApiUser::class)]
abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use LazilyRefreshDatabase;

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
            return ApiUser::paginate(5)->toResourceCollection();
        });

        $router->get('users/{userId}', function ($userId) {
            return ApiUser::find($userId)->toResource();
        });

        $router->get('users/{userId}/with-chaperone-posts', function ($userId) {
            return ApiUser::find($userId)->load('chaperonePosts')->toResource();
        });

        $router->get('posts', function () {
            return ApiPost::paginate(5)->toResourceCollection();
        });

        $router->get('posts/{postId}', function ($postId) {
            return ApiPost::find($postId)->toResource();
        });

        $router->get('things/{id}', function ($id) {
            return new ArrayBackedJsonApiResource(['id' => (int) $id, 'name' => 'test']);
        });

        $router->get('users/{userId}/with-array-relationship', function ($userId) {
            $resource = new UserWithArrayRelationshipResource(ApiUser::find($userId));
            $resource->loadedRelationshipsMap = [
                [new ArrayBackedJsonApiResource(['id' => 99, 'name' => 'test']), 'things', '99', true],
            ];

            return $resource;
        });

        $router->get('users/{userId}/with-duplicate-instances', function ($userId) {
            $instance1 = ApiUser::find($userId);
            $instance2 = ApiUser::find($userId);

            $resource = new UserWithArrayRelationshipResource(ApiUser::find($userId));
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
