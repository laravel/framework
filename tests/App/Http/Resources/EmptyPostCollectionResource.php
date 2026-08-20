<?php

namespace Illuminate\Tests\App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class EmptyPostCollectionResource extends ResourceCollection
{
    public $collects = PostResource::class;
}
