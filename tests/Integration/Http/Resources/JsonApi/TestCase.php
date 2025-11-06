<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
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
    protected function tearDown(): void
    {
        parent::tearDown();

        JsonResource::flushState();
        JsonApiResource::flushState();
    }

    /** {@inheritdoc} */
    #[\Override]
    protected function defineRoutes($router)
    {
        $router->get('users/{userId}', function (Request $request, $userId) {
            return User::find($userId)->toResource();
        });
    }

    /** {@inheritdoc} */
    protected function afterRefreshingDatabase()
    {
        require __DIR__.'/Fixtures/migrations.php';
    }
}
