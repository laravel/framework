<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Exception;
use Illuminate\Http\Resources\Json\JsonResource;

class FilterableObjectResourceWithCallableFields extends JsonResource
{
    public function defaultToArray($request)
    {
        return [
            'id'        => $this->id,
            'full_name' => $this->first_name . ' ' . $this->last_name,
            'addresses' => fn() => throw new Exception('This should be evaluated only if requested, like an eager relationship.'),
        ];
    }
}
