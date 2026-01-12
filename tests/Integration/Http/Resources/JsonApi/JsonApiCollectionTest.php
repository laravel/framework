<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi;

use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\Post;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\Profile;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\Team;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\User;

class JsonApiCollectionTest extends TestCase
{
    public function testItCanGenerateJsonApiResponse()
    {
        $users = User::factory()->times(5)->create();

        $this->getJson('/users')
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertJsonPath(
                'data',
                $users->transform(fn ($user) => [
                    'id' => (string) $user->getKey(),
                    'type' => 'users',
                    'attributes' => [
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                ])->all()
            )->assertJsonMissing(['jsonapi', 'included']);
    }

    public function testItCanGenerateJsonApiResponseWithSparseFieldsets()
    {
        $users = User::factory()->times(5)->create();

        $this->getJson('/users/?'.http_build_query(['fields' => ['users' => 'name']]))
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertJsonPath(
                'data',
                $users->transform(fn ($user) => [
                    'id' => (string) $user->getKey(),
                    'type' => 'users',
                    'attributes' => [
                        'name' => $user->name,
                    ],
                ])->all()
            )->assertJsonMissing(['jsonapi', 'included']);
    }

    public function testItCanGenerateJsonApiResponseWithEmptyRelationshipsUsingSparseIncluded()
    {
        $users = User::factory()->times(5)->create();

        $this->getJson('/users/?'.http_build_query(['include' => 'posts']))
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertJsonPath(
                'data',
                $users->transform(fn ($user) => [
                    'id' => (string) $user->getKey(),
                    'type' => 'users',
                    'attributes' => [
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'relationships' => [
                        'posts' => [
                            'data' => [],
                        ],
                    ],
                ])->all()
            )->assertJsonMissing(['jsonapi', 'included']);
    }

    public function testItCanGenerateJsonApiResponseWithRelationshipsUsingSparseIncluded()
    {
        $now = $this->freezeSecond();

        $users = User::factory()->times(4)->create();
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

        $posts = Post::factory()->times(2)->create([
            'user_id' => $user->getKey(),
        ]);

        $this->getJson('/users?'.http_build_query(['include' => 'profile,posts,teams']))
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertJsonPath(
                'data',
                [
                    ...$users->transform(fn ($user) => [
                        'id' => (string) $user->getKey(),
                        'type' => 'users',
                        'attributes' => [
                            'name' => $user->name,
                            'email' => $user->email,
                        ],
                        'relationships' => [
                            'profile' => ['data' => null],
                            'posts' => ['data' => []],
                            'teams' => ['data' => []],
                        ],
                    ])->all(),
                    [
                        'id' => (string) $user->getKey(),
                        'type' => 'users',
                        'attributes' => [
                            'name' => $user->name,
                            'email' => $user->email,
                        ],
                        'relationships' => [
                            'profile' => [
                                'data' => [
                                    'id' => (string) $profile->getKey(),
                                    'type' => 'profiles',
                                ],
                            ],
                            'posts' => [
                                'data' => [
                                    ['id' => (string) $posts[0]->getKey(), 'type' => 'posts'],
                                    ['id' => (string) $posts[1]->getKey(), 'type' => 'posts'],
                                ],
                            ],
                            'teams' => [
                                'data' => [
                                    ['id' => (string) $team->getKey(), 'type' => 'teams'],
                                ],
                            ],
                        ],
                    ],
                ]
            )->assertJsonPath(
                'included',
                [
                    [
                        'id' => (string) $profile->getKey(),
                        'type' => 'profiles',
                        'attributes' => [
                            'timezone' => 'America/Chicago',
                            'date_of_birth' => '2011-06-09',
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
                            'user_id' => $team->user_id,
                            'name' => 'Laravel Team',
                            'personal_team' => true,
                            'membership' => [
                                'user_id' => $user->getKey(),
                                'team_id' => $team->getKey(),
                                'role' => 'Admin',
                                'created_at' => $now->toISOString(),
                                'updated_at' => $now->toISOString(),
                            ],
                        ],
                    ],
                ]
            );
    }
}
