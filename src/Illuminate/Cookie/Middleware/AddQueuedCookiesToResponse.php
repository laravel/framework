<?php namespace Illuminate\Cookie\Middleware;

use Closure;
use Illuminate\Contracts\Routing\Middleware;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieJar;

class AddQueuedCookiesToResponse implements Middleware {

	/**
	 * The cookie jar instance.
	 *
	 * @var \Illuminate\Cookie\CookieJar
	 */
	protected $cookies;

	/**
	 * Create a new CookieQueue instance.
	 *
	 * @param  \Illuminate\Cookie\CookieJar  $cookies
	 * @return void
	 */
	public function __construct(CookieJar $cookies)
	{
		$this->cookies = $cookies;
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$response = $next($request);

		foreach ($this->cookies->getQueuedCookies() as $cookie)
		{
			$response->headers->setCookie($cookie);
		}

		return $response;
	}

}
