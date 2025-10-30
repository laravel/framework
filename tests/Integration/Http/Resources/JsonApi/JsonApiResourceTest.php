<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;
use Orchestra\Testbench\TestCase;

#[WithMigration]
class JsonApiResourceTest extends TestCase
{
    use RefreshDatabase;

    /** {@inheritdoc} */
    #[\Override]
    protected function defineRoutes($router)
    {
        $router->get('users/{userId}', function (Request $request, $userId) {
            return new UserResource(User::find($userId));
        });
    }

    public function testItCanGenerateJsonApiResponse()
    {
        $user = UserFactory::new()->create();

        $this->getJson('/users/'.$user->getKey())
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertJson([
                'data' => [
                    'id' => (string) $user->getKey(),
                    'type' => 'users',
                    'attributes' => [
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'relationships' => [
                        'data' => [],
                    ],
                ],
            ]);
    }
}

class User extends Authenticatable
{

}

class UserResource extends JsonApiResource
{
    public function toArray(Request $request)
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
