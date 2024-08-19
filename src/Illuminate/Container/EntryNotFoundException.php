<?php

namespace Illuminate\Container;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class EntryNotFoundException extends Exception implements NotFoundExceptionInterface
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $id
     * @param  int  $code
     * @param  \Throwable|null  $previous
     * @return void
     */
    public function __construct($id, $code, $previous)
    {
        parent::__construct("No container bind defined for: {$id}.", $code, $previous);
    }
}
