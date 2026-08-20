<?php

namespace Illuminate\Tests\App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResourceWithOptionalData extends JsonResource
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
            'fourth' => $this->when(false, 'value', 'default'),
            'fifth' => $this->when(false, 'value', function () {
                return 'default';
            }),
        ];
    }
}
