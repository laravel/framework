<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi;

use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\Comment;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\Post;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\Profile;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\Team;
use Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures\User;

class JsonApiResourceTest extends TestCase
{
    public function testItCanGenerateJsonApiResponse()
    {
        $user = User::factory()->create();

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
        $user = User::factory()->create();

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

    public function testItCanGenerateJsonApiResponseWithEmptyRelationshipsUsingSparseIncluded()
    {
        $user = User::factory()->create();

        $this->getJson("/users/{$user->getKey()}?".http_build_query(['include' => 'posts']))
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
                            'data' => [],
                        ],
                    ],
                ],
            ])
            ->assertJsonMissing(['jsonapi', 'included']);
    }

    public function testItCanGenerateJsonApiResponseWithRelationshipsUsingSparseIncluded()
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

        $this->getJson("/users/{$user->getKey()}?".http_build_query(['include' => 'profile,posts,teams']))
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
                                'id' => (string) $profile->getKey(),
                                'type' => 'profiles',
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
                            'user_id' => $user->getKey(),
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
                                'created_at' => $now->toISOString(),
                                'role' => 'Admin',
                                'team_id' => $team->getKey(),
                                'user_id' => $user->getKey(),
                                'updated_at' => $now->toISOString(),
                            ],
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
                                'created_at' => $now->toISOString(),
                                'role' => 'Member',
                                'team_id' => $team->getKey(),
                                'user_id' => $user->getKey(),
                                'updated_at' => $now->toISOString(),
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function test_it_can_resolve_relationship_with_custom_name_and_resource_class()
    {
        $now = $this->freezeSecond();

        $user = User::factory()->create();

        $profile = Profile::factory()->create([
            'user_id' => $user->getKey(),
            'date_of_birth' => '2011-06-09',
            'timezone' => 'America/Chicago',
        ]);

        [$post1, $post2] = Post::factory()->times(2)->create([
            'user_id' => $user->getKey(),
        ]);

        $comment = Comment::factory()->create([
            'post_id' => $post1->getKey(),
            'user_id' => $user->getKey(),
        ]);

        $this->getJson("/posts/{$post1->getKey()}?".http_build_query(['include' => 'author']))
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertExactJson([
                'data' => [
                    'attributes' => [
                        'content' => $post1->content,
                        'title' => $post1->title,
                    ],
                    'type' => 'posts',
                    'id' => (string) $post1->getKey(),
                    'relationships' => [
                        'author' => [
                            'data' => [
                                'id' => (string) $user->getKey(),
                                'type' => 'authors',
                            ],
                        ],
                    ],
                ],
                'included' => [
                    [
                        'attributes' => [
                            'email' => $user->email,
                            'name' => $user->name,
                        ],
                        'id' => (string) $user->getKey(),
                        'type' => 'authors',
                    ],
                ],
            ])
            ->assertJsonMissing(['jsonapi']);
    }

    public function test_it_can_resolve_relationship_with_nested_relationship()
    {
        $now = $this->freezeSecond();

        $user = User::factory()->create();

        $profile = Profile::factory()->create([
            'user_id' => $user->getKey(),
            'date_of_birth' => '2011-06-09',
            'timezone' => 'America/Chicago',
        ]);

        [$post1, $post2] = Post::factory()->times(2)->create([
            'user_id' => $user->getKey(),
        ]);

        $comment = Comment::factory()->create([
            'post_id' => $post1->getKey(),
            'user_id' => $user->getKey(),
        ]);

        $this->getJson("/posts/{$post1->getKey()}?".http_build_query(['include' => 'author,comments.commenter']))
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->dump()
            ->assertExactJson([
                'data' => [
                    'attributes' => [
                        'content' => $post1->content,
                        'title' => $post1->title,
                    ],
                    'type' => 'posts',
                    'id' => (string) $post1->getKey(),
                    'relationships' => [
                        'author' => [
                            'data' => [
                                'id' => (string) $user->getKey(),
                                'type' => 'authors',
                            ],
                        ],
                        'comments' => [
                            'data' => [
                                ['id' => (string) $comment->getKey(), 'type' => 'comments'],
                            ],
                        ],
                    ],
                ],
                'included' => [
                    [
                        'attributes' => [
                            'email' => $user->email,
                            'name' => $user->name,
                        ],
                        'id' => (string) $user->getKey(),
                        'type' => 'authors',
                    ],
                    [
                        'attributes' => [
                            'content' => $comment->content,
                        ],
                        'id' => (string) $comment->getKey(),
                        'type' => 'comments',
                        'relationships' => [
                            'commenter' => [
                                'data' => [
                                    'id' => (string) $user->getKey(),
                                    'type' => 'users',
                                ],
                            ],
                        ],
                    ],
                    [
                        'attributes' => [
                            'email' => $user->email,
                            'name' => $user->name,
                        ],
                        'id' => (string) $user->getKey(),
                        'type' => 'users',
                    ],
                ],
            ])
            ->assertJsonMissing(['jsonapi']);
    }
}
