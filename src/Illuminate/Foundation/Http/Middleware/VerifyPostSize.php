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
		if ($request->server('CONTENT_LENGTH') > $this->getPostMaxSize())
		{
			throw new PostTooLargeException;
		}

		return $next($request);
	}
	
	/**
	 * Determine the server 'post_max_size' as bytes
	 *
	 * @return int
	 */
	protected function getPostMaxSize()
	{
		$post_max_size = trim(ini_get('post_max_size'));
		$last = strtolower($post_max_size[strlen($post_max_size)-1]);
		switch($last) {
			case 'g':
				$post_max_size *= 1024;
			case 'm':
				$post_max_size *= 1024;
			case 'k':
				$post_max_size *= 1024;
		}

		return $post_max_size;
	}
}
