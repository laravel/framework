<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResourceWithOptionalFilledAttributes extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->whenFilled($this->id, 42),
            'title' => $this->whenFilled($this->title, 'no title'),
        ];
    }
}
