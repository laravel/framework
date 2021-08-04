<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResourceWithNoMergeWrap extends JsonResource
{
    protected $wrapOnMerge = false;

    public function toArray($request)
    {
        return [
            'name' => $this->resource->name,
            'preferences' => $this->mergeWhen(true, ['id' => '1', 'name' => 'shop all'])
        ];
    }
}
