<?php namespace Illuminate\Contracts\Exception;

use Closure;
use Exception;

interface Handler {

	/**
	 * Register an application error handler.
	 *
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function error(Closure $callback);

	/**
	 * Register an application error handler at the bottom of the stack.
	 *
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function pushError(Closure $callback);

	/**
	 * Handle an exception for the application.
	 *
	 * @param  \Exception  $exception
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function handleException(Exception $exception);

}
