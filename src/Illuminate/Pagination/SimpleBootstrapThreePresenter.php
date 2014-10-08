<?php namespace Illuminate\Pagination;

use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;

class SimpleBootstrapThreePresenter extends BootstrapThreePresenter {

	/**
	 * Create a simple Bootstrap 3 presenter.
	 *
	 * @param  \Illuminate\Contracts\Pagination\Paginator  $paginator
	 * @return void
	 */
	public function __construct(PaginatorContract $paginator)
	{
		$this->paginator = $paginator;
	}

	/**
	 * Convert the URL window into Bootstrap HTML.
	 *
	 * @return string
	 */
	public function render()
	{
		if ($this->paginator->hasPages() && count($this->paginator->items()) > 0)
		{
			return sprintf(
				'<ul class="pager">%s %s</ul>', $this->getPreviousButton(), $this->getNextButton()
			);
		}

		return '';
	}

}
