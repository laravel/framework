<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PostCollectionResource extends ResourceCollection
{
    public $collects = PostResource::class;

    public function toArray()
    {
        return ['data' => $this->collection];
    }
}
