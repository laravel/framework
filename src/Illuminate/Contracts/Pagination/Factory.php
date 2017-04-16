<?php namespace Illuminate\Contracts\Pagination;

interface Factory {

	/**
	 * Get a new paginator instance.
	 *
	 * @param  array  $items
	 * @param  int    $total
	 * @param  int|null  $perPage
	 * @return \Illuminate\Pagination\Paginator
	 */
	public function make(array $items, $total, $perPage = null);

}