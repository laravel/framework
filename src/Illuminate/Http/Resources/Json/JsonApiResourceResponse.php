<?php

namespace Illuminate\Http\Resources\Json;

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
