<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\Resource;

class PostResourceWithOptionalData extends Resource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'first' => $this->when(false, 'value'),
            'second' => $this->when(true, 'value'),
            'third' => $this->when(true, function () {
                return 'value';
            }),
        ];
    }
}
