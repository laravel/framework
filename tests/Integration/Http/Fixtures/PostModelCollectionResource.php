<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PostModelCollectionResource extends ResourceCollection
{
    public $collects = Post::class;

    public function toArray()
    {
        return ['data' => $this->collection];
    }
}
