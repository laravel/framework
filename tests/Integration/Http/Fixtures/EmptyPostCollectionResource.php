<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\ResourceCollection;

class EmptyPostCollectionResource extends ResourceCollection
{
    public $collects = PostResource::class;
}
