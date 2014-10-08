<?php namespace Illuminate\Pagination;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;

class BootstrapThreePresenter implements Renderable {

	use BootstrapThreeNextPreviousButtonRendererTrait, UrlWindowPresenterTrait;

	/**
	 * The paginator implementation.
	 *
	 * @var \Illuminate\Contracts\Pagination\Paginator
	 */
	protected $paginator;

	/**
	 * The URL window data structure.
	 *
	 * @var array
	 */
	protected $window;

	/**
	 * Create a new Bootstrap presenter instance.
	 *
	 * @param  \Illuminate\Contracts\Pagination\Paginator  $paginator
	 * @return void
	 */
	public function __construct(PaginatorContract $paginator)
	{
		$this->paginator = $paginator;
		$this->window = UrlWindow::make($paginator);
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
				'<ul class="pagination">%s %s %s</ul>', $this->getPreviousButton(),
				$this->getLinks(), $this->getNextButton()
			);
		}

		return '';
	}

	/**
	 * Get HTML wrapper for an available page link.
	 *
	 * @param  string  $url
	 * @param  int  $page
	 * @param  string  $rel
	 * @return string
	 */
	protected function getAvailablePageWrapper($url, $page, $rel = null)
	{
		$rel = is_null($rel) ? '' : ' rel="'.$rel.'"';

		return '<li><a href="'.$url.'"'.$rel.'>'.$page.'</a></li>';
	}

	/**
	 * Get HTML wrapper for disabled text.
	 *
	 * @param  string  $text
	 * @return string
	 */
	protected function getDisabledTextWrapper($text)
	{
		return '<li class="disabled"><span>'.$text.'</span></li>';
	}

	/**
	 * Get HTML wrapper for active text.
	 *
	 * @param  string  $text
	 * @return string
	 */
	protected function getActivePageWrapper($text)
	{
		return '<li class="active"><span>'.$text.'</span></li>';
	}

	/**
	 * Get a pagination "dot" element.
	 *
	 * @return string
	 */
	protected function getDots()
	{
		return $this->getDisabledTextWrapper("...");
	}

	/**
	 * Get the current page from the paginator.
	 *
	 * @return int
	 */
	protected function currentPage()
	{
		return $this->paginator->currentPage();
	}

	/**
	 * Get the last page from the paginator.
	 *
	 * @return int
	 */
	protected function lastPage()
	{
		return $this->paginator->lastPage();
	}

}
