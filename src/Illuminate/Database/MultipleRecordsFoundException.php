<?php

namespace Illuminate\Database;

use RuntimeException;

class MultipleRecordsFoundException extends RuntimeException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        public int $count,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct("$count records were found.", $code, $previous);
    }

    /**
     * Get the number of records found.
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }
}
