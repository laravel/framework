<?php

namespace Illuminate\Pagination;

use RuntimeException;

class OutOfPaginationRangeException extends RuntimeException
{
    /**
     * Set the current page being queried.
     *
     * @param  int  $page
     * @return $this
     */
    public function setPage($page)
    {
        $this->message = "No results found for page $page";

        return $this;
    }
}
