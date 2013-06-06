<?php namespace Illuminate\Pagination;

use Countable;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Illuminate\Support\Collection;

class Paginator implements ArrayAccess, Countable, IteratorAggregate {

	/**
	 * The pagination environment.
	 *
	 * @var \Illuminate\Pagination\Environment
	 */
	protected $env;

	/**
	 * The items being paginated.
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * The total number of items.
	 *
	 * @var int
	 */
	protected $total;

	/**
	 * The amount of items to show per page.
	 *
	 * @var int
	 */
	protected $perPage;

	/**
	 * Get the current page for the request.
	 *
	 * @var int
	 */
	protected $currentPage;

	/**
	 * Get the last available page number.
	 *
	 * @return int
	 */
	protected $lastPage;

	/**
	 * The number of the first item in this range.
	 *
	 * @var int
	 */
	protected $from;

	/**
	 * The number of the last item in this range.
	 *
	 * @var int
	 */
	protected $to;

	/**
	 * All of the additional query string values.
	 *
	 * @var array
	 */
	protected $query = array();

	/**
	 * Create a new Paginator instance.
	 *
	 * @param  \Illuminate\Pagination\Environment  $env
	 * @param  array  $items
	 * @param  int    $total
	 * @param  int    $perPage
	 * @return void
	 */
	public function __construct(Environment $env, array $items, $total, $perPage)
	{
		$this->env = $env;
		$this->total = $total;
		$this->items = $items;
		$this->perPage = $perPage;
	}

	/**
	 * Setup the pagination context (current and last page).
	 *
	 * @return \Illuminate\Pagination\Paginator
	 */
	public function setupPaginationContext()
	{
		$this->calculateCurrentAndLastPages();

		$this->calculateItemRanges();

		return $this;
	}

	/**
	 * Calculate the current and last pages for this instance.
	 *
	 * @return void
	 */
	protected function calculateCurrentAndLastPages()
	{
		$this->lastPage = ceil($this->total / $this->perPage);

		$this->currentPage = $this->calculateCurrentPage($this->lastPage);
	}

	/**
	 * Calculate the first and last item number for this instance.
	 *
	 * @return void
	 */
	protected function calculateItemRanges()
	{
		$this->from = $this->total ? ($this->currentPage - 1) * $this->perPage + 1 : 0;

		$this->to = min($this->total, $this->currentPage * $this->perPage);
	}

	/**
	 * Get the current page for the request.
	 *
	 * @param  int  $lastPage
	 * @return int
	 */
	protected function calculateCurrentPage($lastPage)
	{
		$page = $this->env->getCurrentPage();

		// The page number will get validated and adjusted if it either less than one
		// or greater than the last page available based on the count of the given
		// items array. If it's greater than the last, we'll give back the last.
		if (is_numeric($page) and $page > $lastPage)
		{
			return $lastPage > 0 ? $lastPage : 1;
		}

		return $this->isValidPageNumber($page) ? (int) $page : 1;
	}

	/**
	 * Determine if the given value is a valid page number.
	 *
	 * @param  int  $page
	 * @return bool
	 */
	protected function isValidPageNumber($page)
	{
		return $page >= 1 and filter_var($page, FILTER_VALIDATE_INT) !== false;
	}

	/**
	 * Get the pagination links view.
	 *
	 * @return \Illuminate\View\View
	 */
	public function links()
	{
		return $this->env->getPaginationView($this);
	}

	/**
	 * Get a URL for a given page number.
	 *
	 * @param  int     $page
	 * @return string
	 */
	public function getUrl($page)
	{
		$parameters = array(
			$this->env->getPageName() => $page,
		);

		// If we have any extra query string key / value pairs that need to be added
		// onto the URL, we will put them in query string form and then attach it
		// to the URL. This allows for extra information like sortings storage.
		if (count($this->query) > 0)
		{
			$parameters = array_merge($parameters, $this->query);
		}

		return $this->env->getCurrentUrl().'?'.http_build_query($parameters, null, '&');
	}

	/**
	 * Add a query string value to the paginator.
	 *
	 * @param  string  $key
	 * @param  string  $value
	 * @return \Illuminate\Pagination\Paginator
	 */
	public function appends($key, $value = null)
	{
		if (is_array($key)) return $this->appendArray($key);

		return $this->addQuery($key, $value);
	}

	/**
	 * Add an array of query string values.
	 *
	 * @param  array  $keys
	 * @return \Illuminate\Pagination\Paginator
	 */
	protected function appendArray(array $keys)
	{
		foreach ($keys as $key => $value)
		{
			$this->addQuery($key, $value);
		}

		return $this;
	}

	/**
	 * Add a query string value to the paginator.
	 *
	 * @param  string  $key
	 * @param  string  $value
	 * @return \Illuminate\Pagination\Paginator
	 */
	public function addQuery($key, $value)
	{
		$this->query[$key] = $value;

		return $this;
	}

	/**
	 * Get the current page for the request.
	 *
	 * @return int
	 */
	public function getCurrentPage()
	{
		return $this->currentPage;
	}

	/**
	 * Get the last page that should be available.
	 *
	 * @return int
	 */
	public function getLastPage()
	{
		return $this->lastPage;
	}

	/**
	 * Get the number of the first item on the paginator.
	 *
	 * @return int
	 */
	public function getFrom()
	{
		return $this->from;
	}

	/**
	 * Get the number of the last item on the paginator.
	 *
	 * @return int
	 */
	public function getTo()
	{
		return $this->to;
	}

	/**
	 * Get the number of items to be displayed per page.
	 *
	 * @return int
	 */
	public function getPerPage()
	{
		return $this->perPage;
	}

	/**
	 * Get a collection instance containing the items.
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function getCollection()
	{
		return new Collection($this->items);
	}

	/**
	 * Get the items being paginated.
	 *
	 * @return array
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * Get the total number of items in the collection.
	 *
	 * @return int
	 */
	public function getTotal()
	{
		return $this->total;
	}

	/**
	* Set the base URL in use by the paginator.
	*
	* @param  string  $baseUrl
	* @return void
	*/
	public function setBaseUrl($baseUrl)
	{
		$this->env->setBaseUrl($baseUrl);
	}

	/**
	 * Get the pagination environment.
	 *
	 * @return \Illuminate\Pagination\Environment
	 */
	public function getEnvironment()
	{
		return $this->env;
	}

	/**
	 * Get an iterator for the items.
	 *
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->items);
	}

	/**
	 * Determine if the list of items is empty or not.
	 *
	 * @return bool
	 */
	public function isEmpty()
	{
		return empty($this->items);
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
		return array_key_exists($key, $this->items);
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

}
