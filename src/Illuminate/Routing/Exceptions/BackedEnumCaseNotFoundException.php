<?php

namespace Illuminate\Routing\Exceptions;

use RuntimeException;

class BackedEnumCaseNotFoundException extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $backedEnumClass
     * @param  string  $case
     * @return void
     */
    public function __construct($backedEnumClass, $case)
    {
        parent::__construct("Case [{$case}] not found on Backed Enum [{$backedEnumClass}].");
    }
}
