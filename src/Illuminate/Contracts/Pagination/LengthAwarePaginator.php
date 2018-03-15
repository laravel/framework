<?php

namespace Illuminate\Contracts\Pagination;

interface LengthAwarePaginator extends Paginator
{
    /**
     * Create a range of pagination URLs.
     *
     * @param  int  $start
     * @param  int  $end
     * @return array
     */
    public function getUrlRange($start, $end);

    /**
     * Determine if there are enough items to split into multiple pages.
     *
     * @return bool
     */
    public function hasPages();

    /**
     * Determine if there is more items in the data store.
     *
     * @return bool
     */
    public function hasMorePages();

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
