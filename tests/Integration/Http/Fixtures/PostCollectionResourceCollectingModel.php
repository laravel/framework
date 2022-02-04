<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PostCollectionResourceCollectingModel extends ResourceCollection
{
    public $collects = Post::class;

    public function toArray($request)
    {
        return ['ids' => $this->collection->pluck('id')];
    }
}
