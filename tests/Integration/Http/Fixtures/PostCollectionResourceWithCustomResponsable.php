<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PostCollectionResourceWithCustomResponsable extends ResourceCollection
{
    public $collects = PostResource::class;

    public $resourceResponse = PostResourcePaginatedResponse::class;

    public function toArray($request)
    {
        return ['data' => $this->collection];
    }
}
