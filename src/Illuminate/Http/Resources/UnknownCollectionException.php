<?php

namespace Illuminate\Http\Resources;

use Exception;

class UnknownCollectionException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        parent::__construct(
            'The ['.get_class($resource).'] resource must specify the models it collects.'
        );
    }
}
