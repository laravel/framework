<?php namespace Illuminate\Contracts\Pagination;

interface Presenter {

	/**
	 * Render the given paginator.
	 *
	 * @param  \Illuminate\Contracts\Pagination\Paginator  $paginator
	 * @return string
	 */
	public function render();

	/**
	 * Determine if the underlying paginator being presented has pages to show.
	 *
	 * @return bool
	 */
	public function hasPages();

}
