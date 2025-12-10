<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi;

use Illuminate\Http\Resources\JsonApi\JsonApiRequest;

class JsonApiRequestTest extends TestCase
{
    public function testItCanResolveSparseFields()
    {
        $request = JsonApiRequest::create(uri: '/?'.http_build_query([
            'fields' => [
                'users' => 'name,email',
                'teams' => 'name',
            ],
        ]));

        $this->assertSame(['name', 'email'], $request->sparseFields('users'));
        $this->assertSame(['name'], $request->sparseFields('teams'));
        $this->assertSame([], $request->sparseFields('posts'));
    }

    public function testItCanResolveEmptySparseFields()
    {
        $request = JsonApiRequest::create(uri: '/');

        $this->assertSame([], $request->sparseFields('users'));
        $this->assertSame([], $request->sparseFields('teams'));
        $this->assertSame([], $request->sparseFields('posts'));
    }

    public function testItCanResolveSparseIncluded()
    {
        $request = JsonApiRequest::create(uri: '/?'.http_build_query([
            'include' => 'teams,posts.author,posts.comments,profile.user.profile',
        ]));

        $this->assertSame(['teams', 'posts', 'profile'], $request->sparseIncluded());
        $this->assertSame([], $request->sparseIncluded('teams'));
        $this->assertSame(['author', 'comments'], $request->sparseIncluded('posts'));
        $this->assertSame(['user.profile'], $request->sparseIncluded('profile'));
    }

    public function testItCanResolveEmptySparseIncluded()
    {
        $request = JsonApiRequest::create(uri: '/');

        $this->assertSame([], $request->sparseIncluded());
    }
}
