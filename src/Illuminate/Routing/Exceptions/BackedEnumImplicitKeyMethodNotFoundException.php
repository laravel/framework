<?php

namespace Illuminate\Routing\Exceptions;

use RuntimeException;

class BackedEnumImplicitKeyMethodNotFoundException extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $backedEnumClass
     * @param  string  $key
     * @return void
     */
    public function __construct($backedEnumClass, $key)
    {
        parent::__construct("Implicit key method [{$key}] not found on Backed Enum [{$backedEnumClass}].");
    }
}
