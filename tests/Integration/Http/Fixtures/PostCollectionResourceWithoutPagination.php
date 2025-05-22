<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PostCollectionResourceWithoutPagination extends ResourceCollection
{
    public static $withPagination = false;

    public $collects = PostResource::class;
}
