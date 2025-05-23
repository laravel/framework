<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PostCollectionResourceWithHeaderPagination extends ResourceCollection
{
    public static $withPagination = false;
    public static $withPaginationHeaders = true;

    public static $wrap = null;

    public $collects = PostResource::class;
}
