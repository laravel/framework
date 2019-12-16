<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthorResourceWithHigherOrderWhenProxy extends JsonResource
{
    public function toArray($request)
    {
        $isConditionPasses = $request->conditionPasses === 'true';

        return [
            'name' => $this->when($isConditionPasses)->name,
        ];
    }
}
