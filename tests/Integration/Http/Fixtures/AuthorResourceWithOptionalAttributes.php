<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

class AuthorResourceWithOptionalAttributes extends PostResource
{
    public function toArray($request)
    {
        return [
            'name' => $this->whenSelected('name'),
            'email' => $this->whenSelected('email'),
        ];
    }
}
