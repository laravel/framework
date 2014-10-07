<?php namespace Illuminate\Pagination;

trait UrlWindowPresenterTrait {

	/**
	 * Render the actual link slider.
	 *
	 * @return string
	 */
	protected function getLinks()
	{
		$html = '';

		if (is_array($this->window['first']))
		{
			$html .= $this->getUrlLinks($this->window['first']);
		}

		if (is_array($this->window['slider']))
		{
			$html .= $this->getDots();

			$html .= $this->getUrlLinks($this->window['slider']);
		}

		if (is_array($this->window['last']))
		{
			$html .= $this->getDots();

			$html .= $this->getUrlLinks($this->window['last']);
		}

		return $html;
	}

	/**
	 * Get the links for the URLs in the given array.
	 *
	 * @return array
	 */
	protected function getUrlLinks(array $urls)
	{
		$html = '';

		foreach ($urls as $page => $url)
			$html .= $this->getPageLinkWrapper($url, $page);

		return $html;
	}

	/**
	 * Get the previous page pagination element.
	 *
	 * @param  string  $text
	 * @return string
	 */
	protected function getPreviousButton($text = '&laquo;')
	{
		// If the current page is less than or equal to one, it means we can't go any
		// further back in the pages, so we will render a disabled previous button
		// when that is the case. Otherwise, we will give it an active "status".
		if ($this->currentPage() <= 1)
		{
			return $this->getDisabledTextWrapper($text);
		}

		$url = $this->paginator->url(
			$this->currentPage() - 1
		);

		return $this->getPageLinkWrapper($url, $text, 'prev');
	}

	/**
	 * Get the next page pagination element.
	 *
	 * @param  string  $text
	 * @return string
	 */
	protected function getNextButton($text = '&raquo;')
	{
		// If the current page is greater than or equal to the last page, it means we
		// can't go any further into the pages, as we're already on this last page
		// that is available, so we will make it the "next" link style disabled.
		if ($this->currentPage() >= $this->lastPage())
		{
			return $this->getDisabledTextWrapper($text);
		}

		$url = $this->paginator->url($this->currentPage() + 1);

		return $this->getPageLinkWrapper($url, $text, 'next');
	}

	/**
	 * Get HTML wrapper for a page link.
	 *
	 * @param  string  $url
	 * @param  int  $page
	 * @param  string  $rel
	 * @return string
	 */
	protected function getPageLinkWrapper($url, $page, $rel = null)
	{
		if ($page == $this->paginator->currentPage())
			return $this->getActivePageWrapper($page);

		return $this->getAvailablePageWrapper($url, $page, $rel);
	}

}
