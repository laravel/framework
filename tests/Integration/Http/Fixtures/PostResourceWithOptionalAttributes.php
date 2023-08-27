<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResourceWithOptionalAttributes extends JsonResource
{
    public function toArray()
    {
        return [
            'id' => $this->whenNotNull($this->id),
            'title' => $this->whenNotNull($this->title, 'no title'),
        ];
    }
}
