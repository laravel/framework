<?php

namespace Illuminate\Tests\Integration\Http;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\MergeValue;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Tests\Integration\Http\Fixtures\Post;
use Illuminate\Tests\Integration\Http\Fixtures\Author;
use Illuminate\Http\Resources\ConditionallyLoadsAttributes;
use Illuminate\Tests\Integration\Http\Fixtures\PostResource;
use Illuminate\Tests\Integration\Http\Fixtures\Subscription;
use Illuminate\Tests\Integration\Http\Fixtures\PostCollectionResource;
use Illuminate\Tests\Integration\Http\Fixtures\PostResourceWithoutWrap;
use Illuminate\Tests\Integration\Http\Fixtures\ReallyEmptyPostResource;
use Illuminate\Tests\Integration\Http\Fixtures\SerializablePostResource;
use Illuminate\Tests\Integration\Http\Fixtures\PostResourceWithExtraData;
use Illuminate\Tests\Integration\Http\Fixtures\EmptyPostCollectionResource;
use Illuminate\Tests\Integration\Http\Fixtures\PostResourceWithOptionalData;
use Illuminate\Tests\Integration\Http\Fixtures\PostResourceWithOptionalMerging;
use Illuminate\Tests\Integration\Http\Fixtures\PostResourceWithOptionalRelationship;
use Illuminate\Tests\Integration\Http\Fixtures\AuthorResourceWithOptionalRelationship;
use Illuminate\Tests\Integration\Http\Fixtures\PostResourceWithOptionalPivotRelationship;

/**
 * @group integration
 */
class ResourceTest extends TestCase
{
    public function test_resources_may_be_converted_to_json()
    {
        Route::get('/', function () {
            return new PostResource(new Post([
                'id' => 5,
                'title' => 'Test Title',
            ]));
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'id' => 5,
                'title' => 'Test Title',
            ],
        ]);
    }

    public function test_resources_may_have_no_wrap()
    {
        Route::get('/', function () {
            return new PostResourceWithoutWrap(new Post([
                'id' => 5,
                'title' => 'Test Title',
            ]));
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertJson([
            'id' => 5,
            'title' => 'Test Title',
        ]);
    }

    public function test_resources_may_have_optional_values()
    {
        Route::get('/', function () {
            return new PostResourceWithOptionalData(new Post([
                'id' => 5,
            ]));
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'id' => 5,
                'second' => 'value',
                'third' => 'value',
                'fourth' => 'default',
                'fifth' => 'default',
            ],
        ]);
    }

    public function test_resources_may_have_optional_Merges()
    {
        Route::get('/', function () {
            return new PostResourceWithOptionalMerging(new Post([
                'id' => 5,
            ]));
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertExactJson([
            'data' => [
                'id' => 5,
                'second' => 'value',
            ],
        ]);
    }

    public function test_resources_may_have_optional_relationships()
    {
        Route::get('/', function () {
            return new PostResourceWithOptionalRelationship(new Post([
                'id' => 5,
                'title' => 'Test Title',
            ]));
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertExactJson([
            'data' => [
                'id' => 5,
            ],
        ]);
    }

    public function test_resources_may_load_optional_relationships()
    {
        Route::get('/', function () {
            $post = new Post([
                'id' => 5,
                'title' => 'Test Title',
            ]);

            $post->setRelation('author', new Author(['name' => 'jrrmartin']));

            return new PostResourceWithOptionalRelationship($post);
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertExactJson([
            'data' => [
                'id' => 5,
                'author' => ['name' => 'jrrmartin'],
                'author_name' => 'jrrmartin',
            ],
        ]);
    }

    public function test_resources_may_shows_null_for_loaded_relationship_with_value_null()
    {
        Route::get('/', function () {
            $post = new Post([
                'id' => 5,
                'title' => 'Test Title',
            ]);

            $post->setRelation('author', null);

            return new PostResourceWithOptionalRelationship($post);
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertExactJson([
            'data' => [
                'id' => 5,
                'author' => null,
                'author_name' => null,
            ],
        ]);
    }

    public function test_resources_may_have_optional_relationships_with_default_values()
    {
        Route::get('/', function () {
            return new AuthorResourceWithOptionalRelationship(new Author([
                'name' => 'jrrmartin',
            ]));
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertExactJson([
            'data' => [
                'name' => 'jrrmartin',
                'posts_count' => 'not loaded',
                'latest_post_title' => 'not loaded',
            ],
        ]);
    }

    public function test_resources_may_have_optional_pivot_relationships()
    {
        Route::get('/', function () {
            $post = new Post(['id' => 5]);
            $post->setRelation('pivot', new Subscription);

            return new PostResourceWithOptionalPivotRelationship($post);
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertExactJson([
            'data' => [
                'id' => 5,
                'subscription' => [
                    'foo' => 'bar',
                ],
            ],
        ]);
    }

    public function test_resources_may_have_optional_pivot_relationships_with_custom_accessor()
    {
        Route::get('/', function () {
            $post = new Post(['id' => 5]);
            $post->setRelation('accessor', new Subscription);

            return new PostResourceWithOptionalPivotRelationship($post);
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertExactJson([
            'data' => [
                'id' => 5,
                'custom_subscription' => [
                    'foo' => 'bar',
                ],
            ],
        ]);
    }

    public function test_resource_is_url_routable()
    {
        $post = new PostResource(new Post([
            'id' => 5,
            'title' => 'Test Title',
        ]));

        $this->assertEquals('http://localhost/post/5', url('/post', $post));
    }

    public function test_named_routes_are_url_routable()
    {
        $post = new PostResource(new Post([
            'id' => 5,
            'title' => 'Test Title',
        ]));

        Route::get('/post/{id}', function () use ($post) {
            return route('post.show', $post);
        })->name('post.show');

        $response = $this->withoutExceptionHandling()->get('/post/1');

        $this->assertEquals('http://localhost/post/5', $response->original);
    }

    public function test_resources_may_be_serializable()
    {
        Route::get('/', function () {
            return new SerializablePostResource(new Post([
                'id' => 5,
                'title' => 'Test Title',
            ]));
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'id' => 5,
            ],
        ]);
    }

    public function test_resources_may_customize_responses()
    {
        Route::get('/', function () {
            return new PostResource(new Post([
                'id' => 5,
                'title' => 'Test Title',
            ]));
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);
        $response->assertHeader('X-Resource', 'True');
    }

    public function test_resources_may_customize_extra_data()
    {
        Route::get('/', function () {
            return new PostResourceWithExtraData(new Post([
                'id' => 5,
                'title' => 'Test Title',
            ]));
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertJson([
            'data' => [
                'id' => 5,
                'title' => 'Test Title',
            ],
            'foo' => 'bar',
        ]);
    }

    public function test_resources_may_customize_extra_data_when_building_response()
    {
        Route::get('/', function () {
            return (new PostResourceWithExtraData(new Post([
                'id' => 5,
                'title' => 'Test Title',
            ])))->additional(['baz' => 'qux']);
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertJson([
            'data' => [
                'id' => 5,
                'title' => 'Test Title',
            ],
            'foo' => 'bar',
            'baz' => 'qux',
        ]);
    }

    public function test_custom_headers_may_be_set_on_responses()
    {
        Route::get('/', function () {
            return (new PostResource(new Post([
                'id' => 5,
                'title' => 'Test Title',
            ])))->response()->setStatusCode(202)->header('X-Custom', 'True');
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(202);
        $response->assertHeader('X-Custom', 'True');
    }

    public function test_resources_may_receive_proper_status_code_for_fresh_models()
    {
        Route::get('/', function () {
            $post = new Post([
                'id' => 5,
                'title' => 'Test Title',
            ]);

            $post->wasRecentlyCreated = true;

            return new PostResource($post);
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(201);
    }

    public function test_collections_are_not_doubled_wrapped()
    {
        Route::get('/', function () {
            return new PostCollectionResource(collect([new Post([
                'id' => 5,
                'title' => 'Test Title',
            ])]));
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                [
                    'id' => 5,
                    'title' => 'Test Title',
                ],
            ],
        ]);
    }

    public function test_paginators_receive_links()
    {
        Route::get('/', function () {
            $paginator = new LengthAwarePaginator(
                collect([new Post(['id' => 5, 'title' => 'Test Title'])]),
                10, 15, 1
            );

            return new PostCollectionResource($paginator);
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                [
                    'id' => 5,
                    'title' => 'Test Title',
                ],
            ],
            'links' => [
                'first' => '/?page=1',
                'last' => '/?page=1',
                'prev' => null,
                'next' => null,
            ],
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'path' => '/',
                'per_page' => 15,
                'to' => 1,
                'total' => 10,
            ],
        ]);
    }

    public function test_to_json_may_be_left_off_of_collection()
    {
        Route::get('/', function () {
            return new EmptyPostCollectionResource(new LengthAwarePaginator(
                collect([new Post(['id' => 5, 'title' => 'Test Title'])]),
                10, 15, 1
            ));
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                [
                    'id' => 5,
                    'title' => 'Test Title',
                    'custom' => true,
                ],
            ],
            'links' => [
                'first' => '/?page=1',
                'last' => '/?page=1',
                'prev' => null,
                'next' => null,
            ],
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'path' => '/',
                'per_page' => 15,
                'to' => 1,
                'total' => 10,
            ],
        ]);
    }

    public function test_to_json_may_be_left_off_of_single_resource()
    {
        Route::get('/', function () {
            return new ReallyEmptyPostResource(new Post([
                'id' => 5,
                'title' => 'Test Title',
            ]));
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'id' => 5,
                'title' => 'Test Title',
            ],
        ]);
    }

    public function test_original_on_response_is_model_when_single_resource()
    {
        $createdPost = new Post(['id' => 5, 'title' => 'Test Title']);
        Route::get('/', function () use ($createdPost) {
            return new ReallyEmptyPostResource($createdPost);
        });
        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );
        $this->assertTrue($createdPost->is($response->getOriginalContent()));
    }

    public function test_original_on_response_is_collection_of_model_when_collection_resource()
    {
        $createdPosts = collect([
            new Post(['id' => 5, 'title' => 'Test Title']),
            new Post(['id' => 6, 'title' => 'Test Title 2']),
        ]);
        Route::get('/', function () use ($createdPosts) {
            return new EmptyPostCollectionResource(new LengthAwarePaginator($createdPosts, 10, 15, 1));
        });
        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );
        $createdPosts->each(function ($post) use ($response) {
            $this->assertTrue($response->getOriginalContent()->contains($post));
        });
    }

    public function test_collection_resources_are_countable()
    {
        $posts = collect([
            new Post(['id' => 1, 'title' => 'Test title']),
            new Post(['id' => 2, 'title' => 'Test title 2']),
        ]);

        $collection = new PostCollectionResource($posts);

        $this->assertCount(2, $collection);
        $this->assertSame(2, count($collection));
    }

    public function test_leading_merge__keyed_value_is_merged_correctly()
    {
        $filter = new class {
            use ConditionallyLoadsAttributes;

            public function work()
            {
                return $this->filter([
                    new MergeValue(['name' => 'mohamed', 'location' => 'hurghada']),
                ]);
            }
        };

        $results = $filter->work();

        $this->assertEquals([
            'name' => 'mohamed', 'location' => 'hurghada',
        ], $results);
    }

    public function test_leading_merge_value_is_merged_correctly()
    {
        $filter = new class {
            use ConditionallyLoadsAttributes;

            public function work()
            {
                return $this->filter([
                    new MergeValue(['First', 'Second']),
                    'Taylor',
                    'Mohamed',
                    new MergeValue(['Adam', 'Matt']),
                    'Jeffrey',
                    new MergeValue(['Abigail', 'Lydia']),
                ]);
            }
        };

        $results = $filter->work();

        $this->assertEquals([
            'First', 'Second', 'Taylor', 'Mohamed', 'Adam', 'Matt', 'Jeffrey', 'Abigail', 'Lydia',
        ], $results);
    }

    public function test_merge_values_may_be_missing()
    {
        $filter = new class {
            use ConditionallyLoadsAttributes;

            public function work()
            {
                return $this->filter([
                    new MergeValue(['First', 'Second']),
                    'Taylor',
                    'Mohamed',
                    $this->mergeWhen(false, ['Adam', 'Matt']),
                    'Jeffrey',
                    new MergeValue(['Abigail', 'Lydia']),
                ]);
            }
        };

        $results = $filter->work();

        $this->assertEquals([
            'First', 'Second', 'Taylor', 'Mohamed', 'Jeffrey', 'Abigail', 'Lydia',
        ], $results);
    }

    public function test_initial_merge_values_may_be_missing()
    {
        $filter = new class {
            use ConditionallyLoadsAttributes;

            public function work()
            {
                return $this->filter([
                    $this->mergeWhen(false, ['First', 'Second']),
                    'Taylor',
                    'Mohamed',
                    $this->mergeWhen(true, ['Adam', 'Matt']),
                    'Jeffrey',
                    new MergeValue(['Abigail', 'Lydia']),
                ]);
            }
        };

        $results = $filter->work();

        $this->assertEquals([
            'Taylor', 'Mohamed', 'Adam', 'Matt', 'Jeffrey', 'Abigail', 'Lydia',
        ], $results);
    }

    public function test_merge_value_can_merge_json_serializable()
    {
        $filter = new class {
            use ConditionallyLoadsAttributes;

            public function work()
            {
                $postResource = new PostResource(new Post([
                    'id' => 1,
                    'title' => 'Test Title 1',
                ]));

                return $this->filter([
                    new MergeValue($postResource),
                    'user' => 'test user',
                    'age' => 'test age',
                ]);
            }
        };

        $results = $filter->work();

        $this->assertEquals([
            'id' => 1,
            'title' => 'Test Title 1',
            'custom' => true,
            'user' => 'test user',
            'age' => 'test age',
        ], $results);
    }

    public function test_merge_value_can_merge_collection_of_json_serializable()
    {
        $filter = new class {
            use ConditionallyLoadsAttributes;

            public function work()
            {
                $posts = collect([
                    new Post(['id' => 1, 'title' => 'Test title 1']),
                    new Post(['id' => 2, 'title' => 'Test title 2']),
                ]);

                return $this->filter([
                    new MergeValue(PostResource::collection($posts)),
                ]);
            }
        };

        $results = $filter->work();

        $this->assertEquals([
            ['id' => 1, 'title' => 'Test title 1', 'custom' => true],
            ['id' => 2, 'title' => 'Test title 2', 'custom' => true],
        ], $results);
    }

    public function test_all_merge_values_may_be_missing()
    {
        $filter = new class {
            use ConditionallyLoadsAttributes;

            public function work()
            {
                return $this->filter([
                    $this->mergeWhen(false, ['First', 'Second']),
                    'Taylor',
                    'Mohamed',
                    $this->mergeWhen(false, ['Adam', 'Matt']),
                    'Jeffrey',
                    $this->mergeWhen(false, (['Abigail', 'Lydia'])),
                ]);
            }
        };

        $results = $filter->work();

        $this->assertEquals([
            'Taylor', 'Mohamed', 'Jeffrey',
        ], $results);
    }

    public function test_nested_merges()
    {
        $filter = new class {
            use ConditionallyLoadsAttributes;

            public function work()
            {
                return $this->filter([
                    $this->mergeWhen(true, [['Something']]),
                    [
                        $this->mergeWhen(true, ['First', $this->mergeWhen(true, ['Second'])]),
                        'Third',
                    ],
                    [
                        'Fourth',
                    ],
                ]);
            }
        };

        $results = $filter->work();

        $this->assertEquals([
            [
                'Something',
            ],
            [
                'First', 'Second', 'Third',
            ],
            [
                'Fourth',
            ],
        ], $results);
    }

    public function test_the_resource_can_be_an_array()
    {
        $this->assertJsonResourceResponse([
            'user@example.com' => 'John',
            'admin@example.com' => 'Hank',
        ], [
            'data' => [
                'user@example.com' => 'John',
                'admin@example.com' => 'Hank',
            ],
        ]);
    }

    public function test_it_strips_numeric_keys()
    {
        $this->assertJsonResourceResponse([
            0 => 'John',
            1 => 'Hank',
        ], ['data' => ['John', 'Hank']]);

        $this->assertJsonResourceResponse([
            0 => 'John',
            1 => 'Hank',
            3 => 'Bill',
        ], ['data' => ['John', 'Hank', 'Bill']]);

        $this->assertJsonResourceResponse([
            5 => 'John',
            6 => 'Hank',
        ], ['data' => ['John', 'Hank']]);
    }

    public function test_it_strips_all_keys_if_any_of_them_are_numeric()
    {
        $this->assertJsonResourceResponse([
            '5' => 'John',
            '6' => 'Hank',
            'a' => 'Bill',
        ], ['data' => ['John', 'Hank', 'Bill']]);

        $this->assertJsonResourceResponse([
            5 => 'John',
            6 => 'Hank',
            'a' => 'Bill',
        ], ['data' => ['John', 'Hank', 'Bill']]);
    }

    private function assertJsonResourceResponse($data, $expectedJson)
    {
        Route::get('/', function () use ($data) {
            return new JsonResource($data);
        });

        $this->withoutExceptionHandling()
            ->get('/', ['Accept' => 'application/json'])
            ->assertStatus(200)
            ->assertExactJson($expectedJson);
    }
}
