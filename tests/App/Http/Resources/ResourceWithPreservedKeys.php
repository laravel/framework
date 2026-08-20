<?php

namespace Illuminate\Tests\App\Http\Resources;

class ResourceWithPreservedKeys extends PostResource
{
    protected $preserveKeys = true;

    public function toArray($request)
    {
        return $this->resource;
    }
}
