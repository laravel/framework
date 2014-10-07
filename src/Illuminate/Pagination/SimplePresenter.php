<?php namespace Illuminate\Pagination;

class SimplePresenter extends BootstrapThreePresenter {

	/**
	 * Convert the URL window into Bootstrap HTML.
	 *
	 * @return string
	 */
	public function render()
	{
		if ($this->lastPage() > 1)
		{
			return sprintf(
				'<ul class="pager">%s %s</ul>', $this->getPrevious(), $this->getNext()
			);
		}
	}

}
