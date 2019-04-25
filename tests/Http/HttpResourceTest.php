<?php

namespace Illuminate\Tests\Http;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\JsonResource;

class HttpResourceTest extends TestCase
{
    public function testToResourceArray()
    {
        $request = Request::create('https://example.com');
        $resource = PostResource::make(new Post([
            'id' => 5,
            'title' => 'Test Title',
        ]));

        $this->assertEquals($resource->toArrayResponse($request), [
            'data' => [
                'id' => 5,
                'title' => 'Test Title',
                'custom' => true,
            ],
        ]);
    }

    public function testCollectionToResourceArray()
    {
        $request = Request::create('https://example.com');
        $resource = PostResource::collection(
            collect([new Post(['id' => 5, 'title' => 'Test Title'])]),
            10, 15, 1
        );

        $this->assertEquals($resource->toArrayResponse($request), [
            'data' => [
                [
                    'id' => 5,
                    'title' => 'Test Title',
                    'custom' => true,
                ],
            ],
        ]);
    }

    public function testPaginatedCollectionToResourceArray()
    {
        $request = Request::create('https://example.com');
        $resource = PostResource::collection(new LengthAwarePaginator(
            collect([new Post(['id' => 5, 'title' => 'Test Title'])]),
            10, 15, 1
        ));

        $this->assertEquals($resource->toArrayResponse($request), [
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
}

class Post extends Model
{
    protected $guarded = [];
}

class PostResource extends JsonResource
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
