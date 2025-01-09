<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\JsonResource;

class FilterableObjectResource extends JsonResource
{
    public function defaultToArray($request)
    {
        return [
            'id'        => $this->id,
            'full_name' => $this->first_name . ' ' . $this->last_name,
            'address'   => $this->address,
        ];
    }
}
