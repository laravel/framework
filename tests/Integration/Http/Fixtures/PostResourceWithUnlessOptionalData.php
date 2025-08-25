<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResourceWithUnlessOptionalData extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'first' => $this->unless(false, 'value'),
            'second' => $this->unless(true, 'value'),
            'third' => $this->unless(true, function () {
                return 'value';
            }),
            'fourth' => $this->unless(false, 'value', 'default'),
            'fifth' => $this->unless(false, 'value', function () {
                return 'default';
            }),
        ];
    }
}
