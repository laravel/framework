<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi;

use Illuminate\Http\Resources\JsonApi\JsonApiResource;
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
                            'timezone' => 'America/Chicago',
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

    public function testItCanGenerateJsonApiResponseWithRelationshipsUsingSparseIncludedAndSparseFieldsets()
    {
        $now = $this->freezeSecond();

        $user = User::factory()->create();

        [$post1, $post2] = Post::factory()->times(2)->create([
            'user_id' => $user->getKey(),
        ]);

        $this->getJson('/posts?'.http_build_query(['include' => 'author', 'fields' => ['authors' => 'name']]))
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertExactJson([
                'data' => [
                    [
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
                    [
                        'attributes' => [
                            'content' => $post2->content,
                            'title' => $post2->title,
                        ],
                        'type' => 'posts',
                        'id' => (string) $post2->getKey(),
                        'relationships' => [
                            'author' => [
                                'data' => [
                                    'id' => (string) $user->getKey(),
                                    'type' => 'authors',
                                ],
                            ],
                        ],
                    ],
                ],
                'included' => [
                    [
                        'attributes' => [
                            'name' => $user->name,
                        ],
                        'id' => (string) $user->getKey(),
                        'type' => 'authors',
                    ],
                ],
                'links' => [
                    'first' => url('/posts?page=1'),
                    'last' => url('/posts?page=1'),
                    'next' => null,
                    'prev' => null,
                ],
                'meta' => [
                    'current_page' => 1,
                    'from' => 1,
                    'last_page' => 1,
                    'links' => [
                        ['active' => false, 'label' => '&laquo; Previous', 'page' => null, 'url' => null],
                        ['active' => true, 'label' => '1', 'page' => 1, 'url' => url('/posts?page=1')],
                        ['active' => false, 'label' => 'Next &raquo;', 'page' => null, 'url' => null],
                    ],
                    'path' => url('/posts'),
                    'per_page' => 5,
                    'to' => 2,
                    'total' => 2,
                ],
            ])
            ->assertJsonCount(1, 'included')
            ->assertJsonMissing(['jsonapi']);
    }

    public function testItCanResolveRelationshipWithCustomNameAndResourceClass()
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

    public function testItCanResolveRelationshipWithNestedRelationship()
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

    public function testItCanResolveRelationshipWithRecursiveNestedRelationship()
    {
        $now = $this->freezeSecond();

        $user = User::factory()->create();

        $profile = Profile::factory()->create([
            'user_id' => $user->getKey(),
            'date_of_birth' => '2011-06-09',
            'timezone' => 'America/Chicago',
        ]);

        $this->getJson("/users/{$user->getKey()}?".http_build_query(['include' => 'profile.user.profile']))
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertExactJson([
                'data' => [
                    'attributes' => [
                        'email' => $user->email,
                        'name' => $user->name,
                    ],
                    'id' => (string) $user->getKey(),
                    'type' => 'users',
                    'relationships' => [
                        'profile' => [
                            'data' => ['id' => (string) $profile->getKey(), 'type' => 'profiles'],
                        ],
                    ],
                ],
                'included' => [
                    [
                        'attributes' => [
                            'date_of_birth' => '2011-06-09',
                            'timezone' => 'America/Chicago',
                        ],
                        'id' => (string) $profile->getKey(),
                        'type' => 'profiles',
                        'relationships' => [
                            'user' => [
                                'data' => ['id' => (string) $user->getKey(), 'type' => 'users'],
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
                        'relationships' => [
                            'profile' => [
                                'data' => ['id' => (string) $profile->getKey(), 'type' => 'profiles'],
                            ],
                        ],
                    ],
                ],
            ])
            ->assertJsonMissing(['jsonapi']);
    }

    public function testItCanResolveRelationshipWithRecursiveNestedRelationshipLimitedToDepthConfiguration()
    {
        JsonApiResource::maxRelationshipDepth(2);

        $now = $this->freezeSecond();

        $user = User::factory()->create();

        $profile = Profile::factory()->create([
            'user_id' => $user->getKey(),
            'date_of_birth' => '2011-06-09',
            'timezone' => 'America/Chicago',
        ]);

        $this->getJson("/users/{$user->getKey()}?".http_build_query(['include' => 'profile.user.profile']))
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertExactJson([
                'data' => [
                    'attributes' => [
                        'email' => $user->email,
                        'name' => $user->name,
                    ],
                    'id' => (string) $user->getKey(),
                    'type' => 'users',
                    'relationships' => [
                        'profile' => [
                            'data' => ['id' => (string) $profile->getKey(), 'type' => 'profiles'],
                        ],
                    ],
                ],
                'included' => [
                    [
                        'attributes' => [
                            'date_of_birth' => '2011-06-09',
                            'timezone' => 'America/Chicago',
                        ],
                        'id' => (string) $profile->getKey(),
                        'type' => 'profiles',
                        'relationships' => [
                            'user' => [
                                'data' => ['id' => (string) $user->getKey(), 'type' => 'users'],
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

    public function testItCanResolveRelationshipWithoutRedundantIncludedRelationship()
    {
        $now = $this->freezeSecond();

        $user = User::factory()->create();

        [$post1, $post2] = Post::factory()->times(2)->create([
            'user_id' => $user->getKey(),
        ]);

        $this->getJson('/posts?'.http_build_query(['include' => 'author']))
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertExactJson([
                'data' => [
                    [
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
                    [
                        'attributes' => [
                            'content' => $post2->content,
                            'title' => $post2->title,
                        ],
                        'type' => 'posts',
                        'id' => (string) $post2->getKey(),
                        'relationships' => [
                            'author' => [
                                'data' => [
                                    'id' => (string) $user->getKey(),
                                    'type' => 'authors',
                                ],
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
                'links' => [
                    'first' => url('/posts?page=1'),
                    'last' => url('/posts?page=1'),
                    'next' => null,
                    'prev' => null,
                ],
                'meta' => [
                    'current_page' => 1,
                    'from' => 1,
                    'last_page' => 1,
                    'links' => [
                        ['active' => false, 'label' => '&laquo; Previous', 'page' => null, 'url' => null],
                        ['active' => true, 'label' => '1', 'page' => 1, 'url' => url('/posts?page=1')],
                        ['active' => false, 'label' => 'Next &raquo;', 'page' => null, 'url' => null],
                    ],
                    'path' => url('/posts'),
                    'per_page' => 5,
                    'to' => 2,
                    'total' => 2,
                ],
            ])
            ->assertJsonCount(1, 'included')
            ->assertJsonMissing(['jsonapi']);
    }

    public function testItHandlesBidirectionalRelationshipsWithChaperoneWithoutInfiniteLoop()
    {
        $user = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->getKey(),
        ]);

        // The /with-chaperone-posts route loads chaperonePosts which uses chaperone()
        // to automatically set the inverse 'author' relationship on each Post,
        // creating circular object references (User -> Post -> User same instance).
        // Without the fix, this would hang forever due to infinite loop.
        // The same User model appears twice in included: once as "posts" (the Post)
        // and once as "authors" (Post's author via chaperone) - this is correct
        // because different resource types should not be deduplicated.
        $this->getJson("/users/{$user->getKey()}/with-chaperone-posts?".http_build_query(['include' => 'chaperonePosts']))
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertJsonPath('data.id', (string) $user->getKey())
            ->assertJsonPath('data.type', 'users')
            ->assertJsonPath('data.relationships.chaperonePosts.data.0.type', 'posts')
            ->assertJsonPath('data.relationships.chaperonePosts.data.0.id', (string) $post->getKey())
            ->assertJsonPath('included.0.type', 'posts')
            ->assertJsonPath('included.1.type', 'authors')
            ->assertJsonCount(2, 'included');
    }

    public function testIncludedResourcesCanBeArrayBackedCustomResources()
    {
        $user = User::factory()->create();

        $this->getJson("/users/{$user->getKey()}/with-array-relationship")
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertJsonPath('data.id', (string) $user->getKey())
            ->assertJsonPath('data.type', 'users')
            ->assertJsonPath('included.0.id', '99')
            ->assertJsonPath('included.0.type', 'things')
            ->assertJsonPath('included.0.attributes.name', 'test')
            ->assertJsonCount(1, 'included');
    }

    public function testSameModelWithTheSameResourceTypeIsDeduplicated()
    {
        $user = User::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->getKey(),
        ]);

        $post2 = Post::factory()->create([
            'user_id' => $user->getKey(),
        ]);

        Comment::factory()->create([
            'post_id' => $post1->getKey(),
            'user_id' => $user->getKey(),
        ]);

        Comment::factory()->create([
            'post_id' => $post2->getKey(),
            'user_id' => $user->getKey(),
        ]);

        // Both comments have the same commenter (User). Per the JSON:API spec, the same
        // type+id pair should only appear once in included, even when referenced by
        // multiple relationships. We expect 3 items: 2 comments + 1 user (deduped).
        $response = $this->getJson('/posts?'.http_build_query(['include' => 'comments.commenter']))
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertJsonCount(3, 'included');

        $included = $response->json('included');
        $types = array_column($included, 'type');

        $this->assertCount(2, array_filter($types, fn (string $t) => $t === 'comments'));
        $this->assertCount(1, array_filter($types, fn (string $t) => $t === 'users'));
    }

    public function testSameModelOnDifferentResourcesIsNotDeduplicated()
    {
        $user = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->getKey(),
        ]);

        // Post's author uses AuthorResource ("authors"), root uses UserResource ("users").
        // We don't want to deduplicate them, as even though the underlying model is the
        // same, they are different resource types, so they have different identity.
        $this->getJson("/users/{$user->getKey()}/with-chaperone-posts?".http_build_query(['include' => 'chaperonePosts.author']))
            ->assertHeader('Content-type', 'application/vnd.api+json')
            ->assertJsonPath('data.id', (string) $user->getKey())
            ->assertJsonPath('data.type', 'users')
            ->assertJsonPath('included.0.type', 'posts')
            ->assertJsonPath('included.0.id', (string) $post->getKey())
            ->assertJsonPath('included.1.type', 'authors')
            ->assertJsonPath('included.1.id', (string) $user->getKey())
            ->assertJsonCount(2, 'included');
    }
}
