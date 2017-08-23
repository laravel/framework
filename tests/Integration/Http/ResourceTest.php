<?php

namespace Illuminate\Tests\Integration\Http;

use JsonSerializable;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;

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
            ]
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

        $route = Route::get('/post/{id}', function () use ($post) {
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
            ]
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

    public function test_resources_may_customize_adhoc_extra_data()
    {
        Route::get('/', function () {
            return PostResource::make(new Post([
                'id' => 5,
                'title' => 'Test Title',
            ]))->merge(['foo' => 'bar']);
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
            ]
        ]);
    }
}

class Post extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
}


class PostResource extends Resource
{
    public function toArray($request)
    {
        return ['id' => $this->id, 'title' => $this->title, 'custom' => true];
    }

    public function withResponse($request, $response)
    {
        $response->header('X-Resource', 'True');
    }
}

class PostResourceWithExtraData extends PostResource
{
    public function with($request)
    {
        return ['foo' => 'bar'];
    }
}

class SerializablePostResource extends Resource
{
    public function toArray($request)
    {
        return new JsonSerializableResource($this);
    }
}


class PostCollectionResource extends ResourceCollection
{
    public $collects = PostResource::class;

    public function toArray($request)
    {
        return ['data' => $this->collection];
    }
}

class EmptyPostCollectionResource extends ResourceCollection
{
    public $collects = PostResource::class;
}

class EmptyPostResource extends PostResource
{
    //
}

class ReallyEmptyPostResource extends Resource
{
    //
}

class JsonSerializableResource implements JsonSerializable
{
    public $resource;

    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->resource->id,
        ];
    }
}
