<?php namespace Illuminate\Contracts\Bus;

use Closure;

interface HandlerResolver {

	/**
	 * Get the handler instance for the given command.
	 *
	 * @param  mixed  $command
	 * @return mixed
	 */
	public function resolveHandler($command);

	/**
	 * Get the handler class for the given command.
	 *
	 * @param  mixed  $command
	 * @return string
	 */
	public function getHandlerClass($command);

	/**
	 * Get the handler method for the given command.
	 *
	 * @param  mixed  $command
	 * @return string
	 */
	public function getHandlerMethod($command);

	/**
	 * Register command to handler mappings.
	 *
	 * @param  array  $commands
	 * @return void
	 */
	public function maps(array $commands);

	/**
	 * Register a fallback mapper callback.
	 *
	 * @param  \Closure  $mapper
	 * @return void
	 */
	public function mapUsing(Closure $mapper);

}
