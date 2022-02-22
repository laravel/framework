<?php

namespace Illuminate\Database;

use RuntimeException;

class MultipleRecordsFoundException extends RuntimeException
{
    /**
     * The number of records found.
     *
     * @var int
     */
    protected $count;

    /**
     * MultipleRecordsFoundException constructor.
     *
     * @param  int  $count
     * @param  int  $code
     * @param  \Throwable|null  $previous
     * @return void
     */
    public function __construct($count, $code = 0, $previous = null)
    {
        $this->count = $count;
        parent::__construct("$count records have been found.", $code, $previous);
    }

    /**
     * Get the number of records found..
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }
}
