<?php namespace Illuminate\Foundation\Http\Middleware;

use Closure;
use Illuminate\Contracts\Routing\Middleware;
use Illuminate\Http\Exception\PostTooLargeException;

class VerifyPostSize implements Middleware {

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
     *
     * @throws \Illuminate\Http\Exception\PostTooLargeException
	 */
	public function handle($request, Closure $next)
	{
		if (isset($_SERVER['CONTENT_LENGTH']) && ($_SERVER['CONTENT_LENGTH'] > ini_get('post_max_size')))
		{
			throw new PostTooLargeException;
		}

		return $next($request);
	}

}
