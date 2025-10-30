<?php

namespace Illuminate\Http\Resources\JsonApi;

class ResourceResponse extends \Illuminate\Http\Resources\Json\ResourceResponse
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
