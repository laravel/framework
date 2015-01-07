<?php

namespace Illuminate\Contracts\Pagination;

interface LengthAwarePaginator extends Paginator
{
    /**
     * Determine the total number of items in the data store.
     *
     * @return int
     */
    public function total();

    /**
     * Get the page number of the last available page.
     *
     * @return int
     */
    public function lastPage();
}
