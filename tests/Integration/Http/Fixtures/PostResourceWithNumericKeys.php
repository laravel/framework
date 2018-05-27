<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

class PostResourceWithNumericKeys extends PostResource
{
    public function toArray($request)
    {
        return [
            'array' => [1 => 'foo', 2 => 'bar'],
        ];
    }
}
