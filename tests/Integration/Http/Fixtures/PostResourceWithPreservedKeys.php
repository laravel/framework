<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

class PostResourceWithPreservedKeys extends PostResource
{
    protected $preserveKeys = true;

    public function toArray($request)
    {
        return [
            'array' => [1 => 'foo', 2 => 'bar'],
        ];
    }
}
