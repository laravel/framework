<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

class PostResourceWithExtraMetaData extends PostResource
{
    public function with($request)
    {
        return ['foo' => 'bar'];
    }
}
