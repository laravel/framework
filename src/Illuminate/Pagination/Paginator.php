<?php namespace Illuminate\Pagination;

use Countable;
use ArrayAccess;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Contracts\Pagination\Presenter as PresenterContract;

class Paginator implements Arrayable, ArrayAccess, Countable, IteratorAggregate, Jsonable, PaginatorContract {

	/**
	 * All of the items being paginated.
	 *
	 * @var \Illuminate\Support\Collection
	 */
	protected $items;

	/**
	 * The total number of items before slicing.
	 *
	 * @var int
	 */
	protected $total;

	/**
	 * The number of items to be shown per page.
	 *
	 * @var int
	 */
	protected $perPage;

	/**
	 * The current page being "viewed".
	 *
	 * @var int
	 */
	protected $currentPage;

	/**
	 * The last available page.
	 *
	 * @var int
	 */
	protected $lastPage;

	/**
	 * The base path to assign to all URLs.
	 *
	 * @var string
	 */
	protected $path = '/';

	/**
	 * The query parameters to add to all URLs.
	 *
	 * @var array
	 */
	protected $query = [];

	/**
	 * The URL fragment to add to all URLs.
	 *
	 * @var string
	 */
	protected $fragment = null;

	/**
	 * The query string variable used to store the page.
	 *
	 * @var string
	 */
	protected $pageName = 'page';

	/**
	 * Create a new paginator instance.
	 *
	 * @param  mixed  $items
	 * @param  int  $total
	 * @param  int  $perPage
	 * @param  array  $options (path, query, fragment, pageName)
	 * @return void
	 */
	public function __construct($items, $total, $currentPage, $perPage, array $options = array())
	{
		foreach ($options as $key => $value)
		{
			$this->{$key} = $value;
		}

		$this->total = $total;
		$this->perPage = $perPage;
		$this->lastPage = (int) ceil($this->total / $this->perPage);
		$this->currentPage = $this->setCurrentPage($currentPage, $this->lastPage);
		$this->path = $this->path != '/' ? rtrim($this->path, '/').'/' : $this->path;
		$this->items = $items instanceof Collection ? $items : Collection::make($items);

		if (count($this->items) > $this->perPage)
		{
			$this->items = $this->items->paginate($this->currentPage, $this->perPage);
		}
	}

	/**
	 * Get the current page for the request.
	 *
	 * @param  int  $lastPage
	 * @return int
	 */
	protected function setCurrentPage($currentPage, $lastPage)
	{
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
	 * Determine if the given value is a valid page number.
	 *
	 * @param  int  $page
	 * @return bool
	 */
	protected function isValidPageNumber($page)
	{
		return $page >= 1 && filter_var($page, FILTER_VALIDATE_INT) !== false;
	}

	/**
	 * Get the URLs to the items being paginated.
	 *
	 * @return array
	 */
	public function urls()
	{
		$urls = [];

		for ($page = 1; $page <= $this->lastPage; $page++)
			$urls[] = $this->url($page);

		return $urls;
	}

	/**
	 * Create a range of pagination URLs.
	 *
	 * @param  int  $start
	 * @param  int  $end
	 * @return string
	 */
	public function getUrlRange($start, $end)
	{
		$urls = [];

		for ($page = $start; $page <= $end; $page++)
			$urls[$page] = $this->url($page);

		return $urls;
	}

	/**
	 * Get a URL for a given page number.
	 *
	 * @param  int  $page
	 * @return string
	 */
	public function url($page)
	{
		if ($page > $this->lastPage || $page <= 0) return;

		// If we have any extra query string key / value pairs that need to be added
		// onto the URL, we will put them in query string form and then attach it
		// to the URL. This allows for extra information like sortings storage.
		$parameters = [$this->pageName => $page];

		if (count($this->query) > 0)
		{
			$parameters = array_merge($this->query, $parameters);
		}

		return $this->path.'?'
		                .http_build_query($parameters, null, '&')
		                .$this->buildFragment();
	}

	/**
	 * Build the full fragment portion of a URL.
	 *
	 * @return string
	 */
	protected function buildFragment()
	{
		return $this->fragment ? '#'.$this->fragment : '';
	}

	/**
	 * Get the total number of items being paginated.
	 *
	 * @return int
	 */
	public function totalItems()
	{
		return $this->total;
	}

	/**
	 * Get the slice of items being paginated.
	 *
	 * @return array
	 */
	public function items()
	{
		return $this->items->all();
	}

	/**
	 * Get the number of the first item in the slice.
	 *
	 * @return int
	 */
	public function firstItem()
	{
		return ($this->currentPage - 1) * $this->perPage + 1;
	}

	/**
	 * Get the number of the last item in the slice.
	 *
	 * @return int
	 */
	public function lastItem()
	{
		return min($this->total, $this->firstItem() + $perPage - 1);
	}

	/**
	 * Get the number of items shown per page.
	 *
	 * @return int
	 */
	public function perPage()
	{
		return $this->perPage;
	}

	/**
	 * Get the current page.
	 *
	 * @return int
	 */
	public function currentPage()
	{
		return $this->currentPage;
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
	 * @param  \Illuminate\Contracts\Pagination\Presenter  $presenter
	 * @return string
	 */
	public function render(PresenterContract $presenter = null)
	{
		$presenter = $presenter ?: new BootstrapThreePresenter($this);

		return $presenter->render();
	}

	/**
	 * Get an iterator for the items.
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->items->all());
	}

	/**
	 * Get the number of items for the current page.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->items);
	}

	/**
	 * Determine if the given item exists.
	 *
	 * @param  mixed  $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return array_key_exists($key, $this->items->all());
	}

	/**
	 * Get the item at the given offset.
	 *
	 * @param  mixed  $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return $this->items[$key];
	}

	/**
	 * Set the item at the given offset.
	 *
	 * @param  mixed  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		$this->items[$key] = $value;
	}

	/**
	 * Unset the item at the given key.
	 *
	 * @param  mixed  $key
	 * @return void
	 */
	public function offsetUnset($key)
	{
		unset($this->items[$key]);
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return array(
			'total' => $this->total, 'per_page' => $this->perPage,
			'current_page' => $this->currentPage(), 'last_page' => $this->lastPage(),
			'from' => $this->firstItem(), 'to' => $this->lastItem(), 'data' => $this->items->toArray(),
		);
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
