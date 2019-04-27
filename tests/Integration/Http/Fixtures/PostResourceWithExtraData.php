<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResourceWithExtraData extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'extra_value' => $this->extra['value'],
        ];
    }
}
