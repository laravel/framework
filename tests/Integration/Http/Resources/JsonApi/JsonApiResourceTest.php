<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\Post;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\Profile;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\Team;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\User;
use Orchestra\Testbench\Factories\UserFactory;

class JsonApiResourceTest extends TestCase
{
    public function testItCanGenerateJsonApiResponse()
    {
        $user = UserFactory::new()->create();

        $this->getJson("/users/{$user->getKey()}")
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

    public function testItCanGenerateJsonApiResponseWithSparseFieldsets()
    {
        $user = UserFactory::new()->create();

        $this->getJson("/users/{$user->getKey()}?".http_build_query(['fields' => ['users' => 'name']]))
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertExactJson([
                'data' => [
                    'id' => (string) $user->getKey(),
                    'type' => 'users',
                    'attributes' => [
                        'name' => $user->name,
                    ],
                ],
            ])
            ->assertJsonMissing(['jsonapi', 'included']);
    }

    public function testItCanGenerateJsonApiResponseWithEmptyRelationship()
    {
        $user = UserFactory::new()->create();

        $this->getJson("/users/{$user->getKey()}?".http_build_query(['includes' => ['posts']]))
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
        $now = $this->freezeSecond();
        $user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $user->getKey(),
            'date_of_birth' => '2011-06-09',
            'timezone' => 'America/Chicago',
        ]);

        $team = Team::factory()->create([
            'name' => 'Laravel Team',
        ]);

        $user->teams()->attach($team, ['role' => 'Admin']);
        $user->teams()->attach($team, ['role' => 'Member']);

        $posts = Post::factory()->times(2)->create([
            'user_id' => $user->getKey(),
        ]);

        $this->getJson('/users/'.$user->getKey().'?'.http_build_query(['include' => 'profile,posts,teams']))
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
                            ],
                        ],
                        'profile' => [
                            'data' => [
                                ['id' => (string) $profile->getKey(), 'type' => 'profiles'],
                            ],
                        ],
                        'teams' => [
                            'data' => [
                                ['id' => (string) $team->getKey(), 'type' => 'teams'],
                                ['id' => (string) $team->getKey(), 'type' => 'teams'],
                            ],
                        ],
                    ],
                ],
                'included' => [
                    [
                        'id' => (string) $profile->getKey(),
                        'type' => 'profiles',
                        'attributes' => [
                            'date_of_birth' => '2011-06-09',
                            'id' => $profile->getKey(),
                            'timezone' => 'America/Chicago',
                            'user_id' => 1,
                        ],
                    ],
                    [
                        'id' => (string) $posts[0]->getKey(),
                        'type' => 'posts',
                        'attributes' => [
                            'title' => $posts[0]->title,
                            'content' => $posts[0]->content,
                        ],
                    ],
                    [
                        'id' => (string) $posts[1]->getKey(),
                        'type' => 'posts',
                        'attributes' => [
                            'title' => $posts[1]->title,
                            'content' => $posts[1]->content,
                        ],
                    ],
                    [
                        'id' => (string) $team->getKey(),
                        'type' => 'teams',
                        'attributes' => [
                            'id' => $team->getKey(),
                            'membership' => [
                                'created_at' => $now,
                                'role' => 'Admin',
                                'team_id' => $team->getKey(),
                                'user_id' => $user->getKey(),
                                'updated_at' => $now,
                            ],
                            'name' => 'Laravel Team',
                            'personal_team' => true,
                            'user_id' => $team->user_id,
                        ],
                    ],
                    [
                        'id' => (string) $team->getKey(),
                        'type' => 'teams',
                        'attributes' => [
                            'id' => $team->getKey(),
                            'membership' => [
                                'created_at' => $now,
                                'role' => 'Member',
                                'team_id' => $team->getKey(),
                                'user_id' => $user->getKey(),
                                'updated_at' => $now,
                            ],
                            'name' => 'Laravel Team',
                            'personal_team' => true,
                            'user_id' => $team->user_id,
                        ],
                    ],
                ],
            ]);
    }
}

class ProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
        ];
    }

    #[\Override]
    public function modelName()
    {
        return Profile::class;
    }
}
