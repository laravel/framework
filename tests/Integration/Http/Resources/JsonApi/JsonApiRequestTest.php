<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi;

use Illuminate\Http\Resources\JsonApi\JsonApiRequest;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class JsonApiRequestTest extends TestCase
{
    /**
     * Test that when no sparse fields are requested, the method returns null for any type.
     *
     * @return void
     */
    public function testItReturnsNullWhenNoSparseFieldsAreRequested(): void
    {
        $request = JsonApiRequest::create(uri: '/');

        $this->assertNull($request->sparseFields('users'));
        $this->assertNull($request->sparseFields('teams'));
        $this->assertNull($request->sparseFields('posts'));
    }

    /**
     * Test that when an empty sparse fieldset is requested, the method returns an empty array for that type.
     *
     * @return void
     */
    public function testItReturnsEmptyArrayWhenEmptySparseFieldsetIsRequested(): void
    {
        $request = JsonApiRequest::create(uri: '/?fields[users]=');

        $this->assertSame([], $request->sparseFields('users'));
        // Other types not present in query should return null
        $this->assertNull($request->sparseFields('teams'));
        $this->assertNull($request->sparseFields('posts'));
    }

    /**
     * Test that when sparse fields are requested, the method returns an array of the requested fields for that type.
     *
     * @return void
     */
    public function testItReturnsFieldListWhenSparseFieldsAreRequested(): void
    {
        $request = JsonApiRequest::create(uri: '/?'.http_build_query([
            'fields' => [
                'users' => 'name,email',
                'teams' => 'name',
            ],
        ]));

        $this->assertSame(['name', 'email'], $request->sparseFields('users'));
        $this->assertSame(['name'], $request->sparseFields('teams'));
        $this->assertNull($request->sparseFields('posts'));
    }

    /**
     * Test that when sparse included relationships are requested,
     * the method returns an array of the requested relationships for that type.
     *
     * @return void
     */
    public function testItCanResolveSparseIncluded(): void
    {
        $request = JsonApiRequest::create(uri: '/?'.http_build_query([
            'include' => 'teams,posts.author,posts.comments,profile.user.profile',
        ]));

        $this->assertSame(['teams', 'posts', 'profile'], $request->sparseIncluded());
        $this->assertSame([], $request->sparseIncluded('teams'));
        $this->assertSame(['author', 'comments'], $request->sparseIncluded('posts'));
        $this->assertSame(['user.profile'], $request->sparseIncluded('profile'));
    }

    /**
     * Test that when sparse included relationships are requested with max relationship nesting,
     * the method returns an array of the requested relationships for that type,
     * but only up to the max relationship depth.
     *
     * @return void
     */
    public function testItCanResolveSparseIncludedWithMaxRelationshipNesting(): void
    {
        JsonApiResource::maxRelationshipDepth(2);

        $request = JsonApiRequest::create(uri: '/?'.http_build_query([
            'include' => 'teams,posts.author,posts.comments,profile.user.profile',
        ]));

        $this->assertSame(['teams', 'posts', 'profile'], $request->sparseIncluded());
        $this->assertSame([], $request->sparseIncluded('teams'));
        $this->assertSame(['author', 'comments'], $request->sparseIncluded('posts'));
        $this->assertSame(['user'], $request->sparseIncluded('profile'));
    }

    /**
     * Test that when no sparse included relationships are requested, the method returns an empty array.
     *
     * @return void
     */
    public function testItCanResolveEmptySparseIncluded(): void
    {
        $request = JsonApiRequest::create(uri: '/');

        $this->assertSame([], $request->sparseIncluded());
    }
}
