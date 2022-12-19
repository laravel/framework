<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\ConditionallyLoadsAttributes;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MergeValue;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Tests\Integration\Http\Fixtures\Author;
use Illuminate\Tests\Integration\Http\Fixtures\AuthorResourceWithOptionalRelationship;
use Illuminate\Tests\Integration\Http\Fixtures\EmptyPostCollectionResource;
use Illuminate\Tests\Integration\Http\Fixtures\ObjectResource;
use Illuminate\Tests\Integration\Http\Fixtures\Post;
use Illuminate\Tests\Integration\Http\Fixtures\PostCollectionResource;
use Illuminate\Tests\Integration\Http\Fixtures\PostCollectionResourceWithPaginationInformation;
use Illuminate\Tests\Integration\Http\Fixtures\PostResource;
use Illuminate\Tests\Integration\Http\Fixtures\PostResourceWithAnonymousResourceCollectionWithPaginationInformation;
use Illuminate\Tests\Integration\Http\Fixtures\PostResourceWithExtraData;
use Illuminate\Tests\Integration\Http\Fixtures\PostResourceWithJsonOptions;
use Illuminate\Tests\Integration\Http\Fixtures\PostResourceWithJsonOptionsAndTypeHints;
use Illuminate\Tests\Integration\Http\Fixtures\PostResourceWithOptionalAppendedAttributes;
use Illuminate\Tests\Integration\Http\Fixtures\PostResourceWithOptionalData;
use Illuminate\Tests\Integration\Http\Fixtures\PostResourceWithOptionalMerging;
use Illuminate\Tests\Integration\Http\Fixtures\PostResourceWithOptionalPivotRelationship;
use Illuminate\Tests\Integration\Http\Fixtures\PostResourceWithOptionalRelationship;
use Illuminate\Tests\Integration\Http\Fixtures\PostResourceWithoutWrap;
use Illuminate\Tests\Integration\Http\Fixtures\ReallyEmptyPostResource;
use Illuminate\Tests\Integration\Http\Fixtures\ResourceWithPreservedKeys;
use Illuminate\Tests\Integration\Http\Fixtures\SerializablePostResource;
use Illuminate\Tests\Integration\Http\Fixtures\Subscription;
use Mockery;
use Orchestra\Testbench\TestCase;

class ResourceTest extends TestCase
{
    public function testResourcesMayBeConvertedToJson()
    {
        Route::get('/', function () {
            return new PostResource(new Post([
                'id' => 5,
                'title' => 'Test Title',
                'abstract' => 'Test abstract',
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

    public function testResourcesMayBeConvertedToJsonWithToJsonMethod()
    {
        $resource = new PostResource(new Post([
            'id' => 5,
            'title' => 'Test Title',
            'abstract' => 'Test abstract',
        ]));

        $this->assertSame('{"id":5,"title":"Test Title","custom":true}', $resource->toJson());
    }

    public function testAnObjectsMayBeConvertedToJson()
    {
        Route::get('/', function () {
            return ObjectResource::make(
                (object) ['first_name' => 'Bob', 'age' => 40]
            );
        });

        $this->withoutExceptionHandling()
            ->get('/', ['Accept' => 'application/json'])
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [
                    'name' => 'Bob',
                    'age' => 40,
                ],
            ]);
    }

    public function testArraysWithObjectsMayBeConvertedToJson()
    {
        Route::get('/', function () {
            $objects = [
                (object) ['first_name' => 'Bob', 'age' => 40],
                (object) ['first_name' => 'Jack', 'age' => 25],
            ];

            return ObjectResource::collection($objects);
        });

        $this->withoutExceptionHandling()
            ->get('/', ['Accept' => 'application/json'])
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [
                    ['name' => 'Bob', 'age' => 40],
                    ['name' => 'Jack', 'age' => 25],
                ],
            ]);
    }

    public function testResourcesMayHaveNoWrap()
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

    public function testResourcesMayHaveOptionalValues()
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

    public function testResourcesMayHaveOptionalAppendedAttributes()
    {
        Route::get('/', function () {
            $post = new Post([
                'id' => 5,
            ]);

            $post->append('is_published');

            return new PostResourceWithOptionalAppendedAttributes($post);
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                'id' => 5,
                'first' => true,
                'second' => 'override value',
                'third' => 'override value',
                'fourth' => true,
                'fifth' => true,
            ],
        ]);
    }

    public function testResourcesWithOptionalAppendedAttributesReturnDefaultValuesAndNotMissingValues()
    {
        Route::get('/', function () {
            return new PostResourceWithOptionalAppendedAttributes(new Post([
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
                'fourth' => 'default',
                'fifth' => 'default',
            ],
        ]);
    }

    public function testResourcesMayHaveOptionalMerges()
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

    public function testResourcesMayHaveOptionalRelationships()
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

    public function testResourcesMayLoadOptionalRelationships()
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

    public function testResourcesMayShowsNullForLoadedRelationshipWithValueNull()
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

    public function testResourcesMayHaveOptionalRelationshipsWithDefaultValues()
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

    public function testResourcesMayHaveOptionalPivotRelationships()
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

    public function testResourcesMayHaveOptionalPivotRelationshipsWithCustomAccessor()
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

    public function testResourceIsUrlRoutable()
    {
        $post = new PostResource(new Post([
            'id' => 5,
            'title' => 'Test Title',
        ]));

        $this->assertSame('http://localhost/post/5', url('/post', $post));
    }

    public function testNamedRoutesAreUrlRoutable()
    {
        $post = new PostResource(new Post([
            'id' => 5,
            'title' => 'Test Title',
        ]));

        Route::get('/post/{id}', function () use ($post) {
            return route('post.show', $post);
        })->name('post.show');

        $response = $this->withoutExceptionHandling()->get('/post/1');

        $this->assertSame('http://localhost/post/5', $response->original);
    }

    public function testResourcesMayBeSerializable()
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

    public function testResourcesMayCustomizeResponses()
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

    public function testResourcesMayCustomizeExtraData()
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

    public function testResourcesMayCustomizeExtraDataWhenBuildingResponse()
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

    public function testResourcesMayCustomizeJsonOptions()
    {
        Route::get('/', function () {
            return new PostResourceWithJsonOptions(new Post([
                'id' => 5,
                'title' => 'Test Title',
                'reading_time' => 3.0,
            ]));
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $this->assertEquals(
            '{"data":{"id":5,"title":"Test Title","reading_time":3.0}}',
            $response->baseResponse->content()
        );
    }

    public function testCollectionResourcesMayCustomizeJsonOptions()
    {
        Route::get('/', function () {
            return PostResourceWithJsonOptions::collection(collect([
                new Post(['id' => 5, 'title' => 'Test Title', 'reading_time' => 3.0]),
            ]));
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $this->assertEquals(
            '{"data":[{"id":5,"title":"Test Title","reading_time":3.0}]}',
            $response->baseResponse->content()
        );
    }

    public function testResourcesMayCustomizeJsonOptionsOnPaginatedResponse()
    {
        Route::get('/', function () {
            $paginator = new LengthAwarePaginator(
                collect([new Post(['id' => 5, 'title' => 'Test Title', 'reading_time' => 3.0])]),
                10, 15, 1
            );

            return PostResourceWithJsonOptions::collection($paginator);
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $this->assertEquals(
            '{"data":[{"id":5,"title":"Test Title","reading_time":3.0}],"links":{"first":"\/?page=1","last":"\/?page=1","prev":null,"next":null},"meta":{"current_page":1,"from":1,"last_page":1,"links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"\/?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"path":"\/","per_page":15,"to":1,"total":10}}',
            $response->baseResponse->content()
        );
    }

    public function testResourcesMayCustomizeJsonOptionsWithTypeHintedConstructor()
    {
        Route::get('/', function () {
            return new PostResourceWithJsonOptionsAndTypeHints(new Post([
                'id' => 5,
                'title' => 'Test Title',
                'reading_time' => 3.0,
            ]));
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $this->assertEquals(
            '{"data":{"id":5,"title":"Test Title","reading_time":3.0}}',
            $response->baseResponse->content()
        );
    }

    public function testCustomHeadersMayBeSetOnResponses()
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

    public function testResourcesMayReceiveProperStatusCodeForFreshModels()
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

    public function testCollectionsAreNotDoubledWrapped()
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

    public function testPaginatorsReceiveLinks()
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

    public function testPaginatorResourceCanPreserveQueryParameters()
    {
        Route::get('/', function () {
            $collection = collect([new Post(['id' => 2, 'title' => 'Laravel Nova'])]);
            $paginator = new LengthAwarePaginator(
                $collection, 3, 1, 2
            );

            return PostCollectionResource::make($paginator)->preserveQuery();
        });

        $response = $this->withoutExceptionHandling()->get(
            '/?framework=laravel&author=Otwell&page=2', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                [
                    'id' => 2,
                    'title' => 'Laravel Nova',
                ],
            ],
            'links' => [
                'first' => '/?framework=laravel&author=Otwell&page=1',
                'last' => '/?framework=laravel&author=Otwell&page=3',
                'prev' => '/?framework=laravel&author=Otwell&page=1',
                'next' => '/?framework=laravel&author=Otwell&page=3',
            ],
            'meta' => [
                'current_page' => 2,
                'from' => 2,
                'last_page' => 3,
                'path' => '/',
                'per_page' => 1,
                'to' => 2,
                'total' => 3,
            ],
        ]);
    }

    public function testPaginatorResourceCanReceiveQueryParameters()
    {
        Route::get('/', function () {
            $collection = collect([new Post(['id' => 2, 'title' => 'Laravel Nova'])]);
            $paginator = new LengthAwarePaginator(
                $collection, 3, 1, 2
            );

            return PostCollectionResource::make($paginator)->withQuery(['author' => 'Taylor']);
        });

        $response = $this->withoutExceptionHandling()->get(
            '/?framework=laravel&author=Otwell&page=2', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                [
                    'id' => 2,
                    'title' => 'Laravel Nova',
                ],
            ],
            'links' => [
                'first' => '/?author=Taylor&page=1',
                'last' => '/?author=Taylor&page=3',
                'prev' => '/?author=Taylor&page=1',
                'next' => '/?author=Taylor&page=3',
            ],
            'meta' => [
                'current_page' => 2,
                'from' => 2,
                'last_page' => 3,
                'path' => '/',
                'per_page' => 1,
                'to' => 2,
                'total' => 3,
            ],
        ]);
    }

    public function testCursorPaginatorReceiveLinks()
    {
        Route::get('/', function () {
            $paginator = new CursorPaginator(
                collect([new Post(['id' => 5, 'title' => 'Test Title']), new Post(['id' => 6, 'title' => 'Hello'])]),
                1, null, ['parameters' => ['id']]
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
                'first' => null,
                'last' => null,
                'prev' => null,
                'next' => '/?cursor='.(new Cursor(['id' => 5]))->encode(),
            ],
            'meta' => [
                'path' => '/',
                'per_page' => 1,
            ],
        ]);
    }

    public function testCursorPaginatorResourceCanPreserveQueryParameters()
    {
        Route::get('/', function () {
            $collection = collect([new Post(['id' => 5, 'title' => 'Test Title']), new Post(['id' => 6, 'title' => 'Hello'])]);
            $paginator = new CursorPaginator(
                $collection, 1, null, ['parameters' => ['id']]
            );

            return PostCollectionResource::make($paginator)->preserveQuery();
        });

        $response = $this->withoutExceptionHandling()->get(
            '/?framework=laravel&author=Otwell', ['Accept' => 'application/json']
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
                'first' => null,
                'last' => null,
                'prev' => null,
                'next' => '/?framework=laravel&author=Otwell&cursor='.(new Cursor(['id' => 5]))->encode(),
            ],
            'meta' => [
                'path' => '/',
                'per_page' => 1,
            ],
        ]);
    }

    public function testCursorPaginatorResourceCanReceiveQueryParameters()
    {
        Route::get('/', function () {
            $collection = collect([new Post(['id' => 5, 'title' => 'Test Title']), new Post(['id' => 6, 'title' => 'Hello'])]);
            $paginator = new CursorPaginator(
                $collection, 1, null, ['parameters' => ['id']]
            );

            return PostCollectionResource::make($paginator)->withQuery(['author' => 'Taylor']);
        });

        $response = $this->withoutExceptionHandling()->get(
            '/?framework=laravel&author=Otwell', ['Accept' => 'application/json']
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
                'first' => null,
                'last' => null,
                'prev' => null,
                'next' => '/?author=Taylor&cursor='.(new Cursor(['id' => 5]))->encode(),
            ],
            'meta' => [
                'path' => '/',
                'per_page' => 1,
            ],
        ]);
    }

    public function testToJsonMayBeLeftOffOfCollection()
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

    public function testToJsonMayBeLeftOffOfSingleResource()
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

    public function testOriginalOnResponseIsModelWhenSingleResource()
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

    public function testOriginalOnResponseIsCollectionOfModelWhenCollectionResource()
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

    public function testCollectionResourceWithPaginationInfomation()
    {
        $posts = collect([
            new Post(['id' => 5, 'title' => 'Test Title']),
        ]);

        Route::get('/', function () use ($posts) {
            return new PostCollectionResourceWithPaginationInformation(new LengthAwarePaginator($posts, 10, 1, 1));
        });

        $response = $this->withoutExceptionHandling()->get(
            '/',
            ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                [
                    'id' => 5,
                    'title' => 'Test Title',
                ],
            ],
            'current_page' => 1,
            'per_page' => 1,
            'total_page' => 10,
            'total' => 10,
        ]);
    }

    public function testResourceWithPaginationInfomation()
    {
        $posts = collect([
            new Post(['id' => 5, 'title' => 'Test Title']),
        ]);

        Route::get('/', function () use ($posts) {
            return PostResourceWithAnonymousResourceCollectionWithPaginationInformation::collection(new LengthAwarePaginator($posts, 10, 1, 1));
        });

        $response = $this->withoutExceptionHandling()->get(
            '/',
            ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertJson([
            'data' => [
                [
                    'id' => 5,
                    'title' => 'Test Title',
                ],
            ],
            'current_page' => 1,
            'per_page' => 1,
            'total_page' => 10,
            'total' => 10,
        ]);
    }

    public function testCollectionResourcesAreCountable()
    {
        $posts = collect([
            new Post(['id' => 1, 'title' => 'Test title']),
            new Post(['id' => 2, 'title' => 'Test title 2']),
        ]);

        $collection = new PostCollectionResource($posts);

        $this->assertCount(2, $collection);
        $this->assertCount(2, $collection);
    }

    public function testKeysArePreservedIfTheResourceIsFlaggedToPreserveKeys()
    {
        $data = [
            'authorBook' => [
                'byId' => [
                    1 => [
                        'id' => 1,
                        'authorId' => 5,
                        'bookId' => 22,
                    ],
                    2 => [
                        'id' => 2,
                        'authorId' => 5,
                        'bookId' => 15,
                    ],
                    3 => [
                        'id' => 3,
                        'authorId' => 42,
                        'bookId' => 12,
                    ],
                ],
                'allIds' => [1, 2, 3],
            ],
        ];

        Route::get('/', function () use ($data) {
            return new ResourceWithPreservedKeys($data);
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertJson(['data' => $data]);
    }

    public function testKeysArePreservedInAnAnonymousColletionIfTheResourceIsFlaggedToPreserveKeys()
    {
        $data = Collection::make([
            [
                'id' => 1,
                'authorId' => 5,
                'bookId' => 22,
            ],
            [
                'id' => 2,
                'authorId' => 5,
                'bookId' => 15,
            ],
            [
                'id' => 3,
                'authorId' => 42,
                'bookId' => 12,
            ],
        ])->keyBy->id;

        Route::get('/', function () use ($data) {
            return ResourceWithPreservedKeys::collection($data);
        });

        $response = $this->withoutExceptionHandling()->get(
            '/', ['Accept' => 'application/json']
        );

        $response->assertStatus(200);

        $response->assertJson(['data' => $data->toArray()]);
    }

    public function testLeadingMergeKeyedValueIsMergedCorrectly()
    {
        $filter = new class
        {
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

    public function testPostTooLargeException()
    {
        $this->expectException(PostTooLargeException::class);

        $request = Mockery::mock(Request::class, ['server' => ['CONTENT_LENGTH' => '2147483640']]);
        $post = new ValidatePostSize;
        $post->handle($request, function () {
        });
    }

    public function testLeadingMergeKeyedValueIsMergedCorrectlyWhenFirstValueIsMissing()
    {
        $filter = new class
        {
            use ConditionallyLoadsAttributes;

            public function work()
            {
                return $this->filter([
                    new MergeValue([
                        0 => new MissingValue,
                        'name' => 'mohamed',
                        'location' => 'hurghada',
                    ]),
                ]);
            }
        };

        $results = $filter->work();

        $this->assertEquals([
            'name' => 'mohamed', 'location' => 'hurghada',
        ], $results);
    }

    public function testLeadingMergeValueIsMergedCorrectly()
    {
        $filter = new class
        {
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

    public function testMergeValuesMayBeMissing()
    {
        $filter = new class
        {
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

    public function testInitialMergeValuesMayBeMissing()
    {
        $filter = new class
        {
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

    public function testMergeValueCanMergeJsonSerializable()
    {
        $filter = new class
        {
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

    public function testMergeValueCanMergeCollectionOfJsonSerializable()
    {
        $filter = new class
        {
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

    public function testAllMergeValuesMayBeMissing()
    {
        $filter = new class
        {
            use ConditionallyLoadsAttributes;

            public function work()
            {
                return $this->filter([
                    $this->mergeWhen(false, ['First', 'Second']),
                    'Taylor',
                    'Mohamed',
                    $this->mergeWhen(false, ['Adam', 'Matt']),
                    'Jeffrey',
                    $this->mergeWhen(false, ['Abigail', 'Lydia']),
                ]);
            }
        };

        $results = $filter->work();

        $this->assertEquals([
            'Taylor', 'Mohamed', 'Jeffrey',
        ], $results);
    }

    public function testNestedMerges()
    {
        $filter = new class
        {
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

    public function testTheResourceCanBeAnArray()
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

    public function testItWillReturnAsAnArrayWhenStringKeysAreStripped()
    {
        $this->assertJsonResourceResponse([
            1 => 'John',
            2 => 'Hank',
            'foo' => new MissingValue,
        ], ['data' => ['John', 'Hank']]);

        $this->assertJsonResourceResponse([
            1 => 'John',
            'foo' => new MissingValue,
            3 => 'Hank',
        ], ['data' => ['John', 'Hank']]);

        $this->assertJsonResourceResponse([
            'foo' => new MissingValue,
            2 => 'John',
            3 => 'Hank',
        ], ['data' => ['John', 'Hank']]);
    }

    public function testItStripsNumericKeys()
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

    public function testItWontKeysIfAnyOfThemAreStrings()
    {
        $this->assertJsonResourceResponse([
            '5' => 'John',
            '6' => 'Hank',
            'a' => 'Bill',
        ], ['data' => ['5' => 'John', '6' => 'Hank', 'a' => 'Bill']]);

        $this->assertJsonResourceResponse([
            0 => 10,
            1 => 20,
            'total' => 30,
        ], ['data' => [0 => 10, 1 => 20, 'total' => 30]]);
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
