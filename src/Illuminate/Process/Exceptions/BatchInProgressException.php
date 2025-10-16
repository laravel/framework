<?php

namespace Illuminate\Process\Exceptions;

use Symfony\Component\Process\Exception\RuntimeException;

class BatchInProgressException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('You cannot add processes to a batch that is already in progress.');
    }
}
