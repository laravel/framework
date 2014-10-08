<?php namespace Illuminate\Pagination;

class SimpleBootstrapThreePresenter {

	use BootstrapThreeNextPreviousButtonRendererTrait;

	/**
	 * The paginator implementation.
	 *
	 * @var \Illuminate\Contracts\Pagination\Paginator
	 */
	protected $paginator;

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
		if ($this->paginator->hasPages())
		{
			return sprintf(
				'<ul class="pager">%s %s</ul>', $this->getPrevious(), $this->getNext()
			);
		}
	}

}
