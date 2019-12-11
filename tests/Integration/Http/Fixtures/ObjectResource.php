<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\JsonResource;

class ObjectResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'name' => $this->first_name,
            'age' => $this->age,
        ];
    }
}
