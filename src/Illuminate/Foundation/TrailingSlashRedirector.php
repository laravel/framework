<?php namespace Illuminate\Foundation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TrailingSlashRedirector implements HttpKernelInterface {

	/**
	 * The wrapped HttpKernel.
	 *
	 * @var \Symfony\Component\HttpKernel\HttpKernelInterface
	 */
	protected $app;

	/**
	 * Create a new instance of the middleware.
	 *
	 * @param  \Symfony\Component\HttpKernel\HttpKernelInterface
	 * @return void
	 */
	public function __construct(HttpKernelInterface $app)
	{
		$this->app = $app;
	}

	/**
	 * Handle an incoming request and convert it to a response.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  int  $type
	 * @param  bool  $catch
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
	{
		if ($this->runningInConsole()) return;

		// Here we will check if the request path ends in a single trailing slash and
		// redirect it using a 301 response code if it does which avoids duplicate
		// content in this application while still providing a solid experience.
		if ($this->hasTrailingSlash($request->getPathInfo()))
		{
			return $this->redirectWithoutSlash($request);
		}

		return $this->app->handle($request, $type, $catch);
	}

	/**
	 * Determine if the given path has a trailing slash.
	 *
	 * @param  string  $path
	 * @return string
	 */
	protected function hasTrailingSlash($path)
	{
		return ($path != '/' and ends_with($path, '/') and ! ends_with($path, '//'));
	}

	/**
	 * Send a redirect response without the trailing slash.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	protected function redirectWithoutSlash(Request $request)
	{
		return new RedirectResponse($this->fullUrl($request), 301);
	}

	/**
	 * Get the full URL for the request.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return string
	 */
	protected function fullUrl(Request $request)
	{
		$query = $request->getQueryString();

		$url = rtrim(preg_replace('/\?.*/', '', $request->getUri()), '/');

		return $query ? $url.'?'.$query : $url;
	}

	/**
	 * Determine if we are running in a console.
	 *
	 * @return bool
	 */
	protected function runningInConsole()
	{
		return php_sapi_name() == 'cli';
	}

}