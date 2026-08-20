<?php

namespace Illuminate\Tests\App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PostModelCollectionResource extends ResourceCollection
{
    public $collects = Post::class;

    public function toArray($request)
    {
        return ['data' => $this->collection];
    }
}
