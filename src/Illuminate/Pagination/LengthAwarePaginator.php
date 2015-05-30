<?php namespace Illuminate\Pagination;

use Countable;
use ArrayAccess;
use IteratorAggregate;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Pagination\Presenter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;

class LengthAwarePaginator extends AbstractPaginator implements Arrayable, ArrayAccess, Countable, IteratorAggregate, Jsonable, LengthAwarePaginatorContract {

	/**
	 * The total number of items before slicing.
	 *
	 * @var int
	 */
	protected $total;

	/**
	 * The last available page.
	 *
	 * @var int
	 */
	protected $lastPage;

	/**
	 * Create a new paginator instance.
	 *
	 * @param  mixed  $items
	 * @param  int  $total
	 * @param  int  $perPage
	 * @param  int|null  $currentPage
	 * @param  array  $options (path, query, fragment, pageName)
	 * @return void
	 */
	public function __construct($items, $total, $perPage, $currentPage = null, array $options = [])
	{
		foreach ($options as $key => $value)
		{
			$this->{$key} = $value;
		}

		$this->total = $total;
		$this->perPage = $perPage;
		$this->lastPage = (int) ceil($total / $perPage);
		$this->currentPage = $this->setCurrentPage($currentPage, $this->lastPage);
		$this->path = $this->path != '/' ? rtrim($this->path, '/').'/' : $this->path;
		$this->items = $items instanceof Collection ? $items : Collection::make($items);
	}

	/**
	 * Get the current page for the request.
	 *
	 * @param  int  $currentPage
	 * @param  int  $lastPage
	 * @return int
	 */
	protected function setCurrentPage($currentPage, $lastPage)
	{
		$currentPage = $currentPage ?: static::resolveCurrentPage();

		// The page number will get validated and adjusted if it either less than one
		// or greater than the last page available based on the count of the given
		// items array. If it's greater than the last, we'll give back the last.
		if (is_numeric($currentPage) && $currentPage > $lastPage)
		{
			return $lastPage > 0 ? $lastPage : 1;
		}

		return $this->isValidPageNumber($currentPage) ? (int) $currentPage : 1;
	}

	/**
	 * Get the URL for the next page.
	 *
	 * @return string
	 */
	public function nextPageUrl()
	{
		if ($this->lastPage() > $this->currentPage())
		{
			return $this->url($this->currentPage() + 1);
		}
	}

	/**
	 * Determine if there are more items in the data source.
	 *
	 * @return bool
	 */
	public function hasMorePages()
	{
		return $this->currentPage() < $this->lastPage();
	}

	/**
	 * Get the total number of items being paginated.
	 *
	 * @return int
	 */
	public function total()
	{
		return $this->total;
	}

	/**
	 * Get the last page.
	 *
	 * @return int
	 */
	public function lastPage()
	{
		return $this->lastPage;
	}

	/**
	 * Render the paginator using the given presenter.
	 *
	 * @param  \Illuminate\Contracts\Pagination\Presenter|null  $presenter
	 * @return string
	 */
	public function render(Presenter $presenter = null)
	{
		if (is_null($presenter) && static::$presenterResolver)
		{
			$presenter = call_user_func(static::$presenterResolver, $this);
		}

		$presenter = $presenter ?: new BootstrapThreePresenter($this);

		return $presenter->render();
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return [
			'total'         => $this->total(),
			'per_page'      => $this->perPage(),
			'current_page'  => $this->currentPage(),
			'last_page'     => $this->lastPage(),
			'next_page_url' => $this->nextPageUrl(),
			'prev_page_url' => $this->previousPageUrl(),
			'from'          => $this->firstItem(),
			'to'            => $this->lastItem(),
			'data'          => $this->items->toArray(),
		];
	}

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param  int  $options
	 * @return string
	 */
	public function toJson($options = 0)
	{
		return json_encode($this->toArray(), $options);
	}

}
