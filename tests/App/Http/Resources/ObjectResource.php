<?php

namespace Illuminate\Tests\App\Http\Resources;

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
