<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PostCollectionResourceWithoutWrapAndExtraData extends ResourceCollection
{
    public static $withPagination = false;

    public static $wrap = null;

    public $collects = PostResource::class;

    public function with($request) {
        return [
            'extra_data' => 'extra_value',
        ];
    }
}
