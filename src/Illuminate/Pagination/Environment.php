<?php namespace Illuminate\Pagination;

use Illuminate\Http\Request;
use Illuminate\View\Environment as ViewEnvironment;
use Symfony\Component\Translation\TranslatorInterface;

class Environment {

	/**
	 * The request instance.
	 *
	 * @var Symfony\Component\HttpFoundation\Request
	 */
	protected $request;

	/**
	 * The view environment instance.
	 *
	 * @var Illuminate\View\Environment
	 */
	protected $view;

	/**
	 * The translator implementation.
	 *
	 * @var Symfony\Component\Translation\TranslatorInterface
	 */
	protected $trans;

	/**
	 * The name of the pagination view.
	 *
	 * @var string
	 */
	protected $viewName;

	/**
	 * The number of the current page.
	 *
	 * @var int
	 */
	protected $currentPage;

	/**
	 * The locale to be used by the translator.
	 *
	 * @var string
	 */
	protected $locale;

	/**
	 * The base URL in use by the paginator.
	 *
	 * @var string
	 */
	protected $baseUrl;

	/**
	 * The input parameter used for the current page.
	 *
	 * @var string
	 */
	protected $pageParam = 'page';

	/**
	 * Create a new pagination environment.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  Illuminate\View\Environment  $view
	 * @param  Illuminate\Translation\TranslatorInterface  $trans
	 * @return void
	 */
	public function __construct(Request $request, ViewEnvironment $view, TranslatorInterface $trans, $pageParam = null)
	{
		$this->view = $view;
		$this->trans = $trans;
		$this->request = $request;
		$this->setupPaginationEnvironment();

		if (isset($pageParam))
		{
			$this->pageParam = $pageParam;
		}
	}

	/**
	 * Setup the pagination environment.
	 *
	 * @return void
	 */
	protected function setupPaginationEnvironment()
	{
		$this->view->addNamespace('pagination', __DIR__.'/views');
	}

	/**
	 * Get a new paginator instance.
	 *
	 * @param  array  $items
	 * @param  int    $perPage
	 * @param  int    $total
	 * @return Illuminate\Pagination\Paginator
	 */
	public function make(array $items, $total, $perPage)
	{
		$paginator = new Paginator($this, $items, $total, $perPage);

		return $paginator->setupPaginationContext();
	}

	/**
	 * Get the pagination view.
	 *
	 * @param  Illuminate\Pagination\Paginator  $paginator
	 * @return Illuminate\View\View
	 */
	public function getPaginationView(Paginator $paginator)
	{
		$data = array('environment' => $this, 'paginator' => $paginator);

		return $this->view->make($this->getViewName(), $data);
	}

	/**
	 * Get the number of the current page.
	 *
	 * @return int
	 */
	public function getCurrentPage()
	{
		return $this->currentPage ?: $this->request->query->get($this->pageParam, 1);
	}

	/**
	 * Set the number of the current page.
	 *
	 * @param  int  $number
	 * @return void
	 */
	public function setCurrentPage($number)
	{
		$this->currentPage = $number;
	}

	/**
	 * Get the root URL for the request.
	 *
	 * @return string
	 */
	public function getCurrentUrl()
	{
		return $this->baseUrl ?: $this->request->url();
	}

	/**
	 * Set the base URL in use by the paginator.
	 *
	 * @param  string  $baseUrl
	 * @return void
	 */
	public function setBaseUrl($baseUrl)
	{
		$this->baseUrl = $baseUrl;
	}

	/**
	 * Set the input page parameter in use by the paginator.
	 *
	 * @param  string  $pageParam
	 * @return void
	 */
	public function setPageParam($pageParam)
	{
		$this->pageParam = $pageParam;
	}

	/**
	 * Get the input page parameter in use by the paginator.
	 *
	 * @return string
	 */
	public function getPageParam()
	{
		return $this->pageParam;
	}

	/**
	 * Get the name of the pagination view.
	 *
	 * @return string
	 */
	public function getViewName()
	{
		return $this->viewName ?: 'pagination::slider';
	}

	/**
	 * Set the name of the pagination view.
	 *
	 * @param  string  $viewName
	 * @return void
	 */
	public function setViewName($viewName)
	{
		$this->viewName = $viewName;
	}

	/**
	 * Get the locale of the paginator.
	 *
	 * @return string
	 */
	public function getLocale()
	{
		return $this->locale;
	}

	/**
	 * Set the locale of the paginator.
	 *
	 * @param  string  $locale
	 * @return void
	 */
	public function setLocale($locale)
	{
		$this->locale = $locale;
	}

	/**
	 * Get the active request instance.
	 *
	 * @return Symfony\Component\HttpFoundation\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Set the active request instance.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return void
	 */
	public function setRequest(Request $request)
	{
		$this->request = $request;
	}

	/**
	 * Get the current view driver.
	 *
	 * @return Illuminate\View\Environment
	 */
	public function getViewDriver()
	{
		return $this->view;
	}

	/**
	 * Set the current view driver.
	 *
	 * @param  Illuminate\View\Environment  $view
	 * @return void
	 */
	public function setViewDriver(ViewEnvironment $view)
	{
		$this->view = $view;
	}

	/**
	 * Get the translator instance.
	 *
	 * @return Symfony\Component\Translation\TranslatorInterface
	 */
	public function getTranslator()
	{
		return $this->trans;
	}

}
