<?php

namespace Illuminate\Tests\App\Http\Resources;

class PostResourceWithExtraData extends PostResource
{
    public function with($request)
    {
        return ['foo' => 'bar'];
    }
}
