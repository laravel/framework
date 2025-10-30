<?php

namespace Illuminate\Http\Resources\JsonApi;

use Illuminate\Http\Resources\Json\ResourceResponse;

class JsonApiResourceResponse extends ResourceResponse
{
    /**
     * Get the default data wrapper for the resource.
     *
     * @return string
     */
    #[\Override]
    protected function wrapper()
    {
        return 'data';
    }
}
