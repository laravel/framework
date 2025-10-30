<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi;

use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Support\Facades\Schema;
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
            $user = User::find($userId);

            if (! empty($includes = $request->array('includes'))) {
                $user->loadMissing($includes);
            }

            return $user->toResource();
        });
    }

    /** {@inheritdoc} */
    protected function afterRefreshingDatabase()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('title');
            $table->text('content');
            $table->timestamps();
        });
    }

    public function testBaseJsonResourceCanBeConvertedToJsonApiResource()
    {
        $user = UserFactory::new()->create();

        $resource = (new UserResource($user))->asJsonApi();

        $this->assertInstanceOf(JsonApiResource::class, $resource);
    }

    public function testItCanGenerateJsonApiResponse()
    {
        $user = UserFactory::new()->create();

        $this->getJson('/users/'.$user->getKey())
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertExactJson([
                'data' => [
                    'id' => (string) $user->getKey(),
                    'type' => 'users',
                    'attributes' => [
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                ],
            ])
            ->assertJsonMissing(['jsonapi', 'included']);
    }

    public function testItCanGenerateJsonApiResponseWithEagerLoadedRelationship()
    {
        $user = UserFactory::new()->create();

        $posts = PostFactory::new()->times(2)->create([
            'user_id' => $user->getKey(),
        ]);

        $this->getJson('/users/'.$user->getKey().'?'.http_build_query(['includes' => ['posts']]))
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertExactJson([
                'data' => [
                    'id' => (string) $user->getKey(),
                    'type' => 'users',
                    'attributes' => [
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'relationships' => [
                        'posts' => [
                            'data' => [
                                ['id' => (string) $posts[0]->getKey(), 'type' => 'posts'],
                                ['id' => (string) $posts[1]->getKey(), 'type' => 'posts'],
                            ]
                        ],
                    ],
                ],
                'included' => [
                    [
                        'id' => (string) $posts[0]->getKey(),
                        'type' => 'posts',
                        'attributes' => ['title' => $posts[0]->title, 'content' => $posts[0]->content],
                    ],
                    [
                        'id' => (string) $posts[1]->getKey(),
                        'type' => 'posts',
                        'attributes' => ['title' => $posts[1]->title, 'content' => $posts[1]->content],
                    ],
                ],
            ]);
    }
}

#[UseResource(UserApiResource::class)]
class User extends Authenticatable
{
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

class UserResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}

class UserApiResource extends JsonApiResource
{
    public function toArray(Request $request)
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}


#[UseResource(PostApiResource::class)]
class Post extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

class PostApiResource extends JsonApiResource
{
    public function toArray(Request $request)
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
        ];
    }
}

class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
            'title' => $this->faker->word(),
            'content' => $this->faker->words(10, true),
        ];
    }

    #[\Override]
    public function modelName()
    {
        return Post::class;
    }
}
