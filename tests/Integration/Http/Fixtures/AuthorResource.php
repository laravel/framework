<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Http\Resources\Json\Resource;

class AuthorResource extends Resource
{
    public function toArray($request)
    {
        return ['name' => $this->name];
    }
}
