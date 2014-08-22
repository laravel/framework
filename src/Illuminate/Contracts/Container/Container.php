<?php namespace Illuminate\Contracts\Container;

interface Container {

	/**
	 * Determine if the given abstract type has been bound.
	 *
	 * @param  string  $abstract
	 * @return bool
	 */
	public function bound($abstract);

	/**
	 * Register a binding with the container.
	 *
	 * @param  string|array  $abstract
	 * @param  \Closure|string|null  $concrete
	 * @param  bool  $shared
	 * @return void
	 */
	public function bind($abstract, $concrete = null, $shared = false);

	/**
	 * Register a binding if it hasn't already been registered.
	 *
	 * @param  string  $abstract
	 * @param  \Closure|string|null  $concrete
	 * @param  bool  $shared
	 * @return void
	 */
	public function bindIf($abstract, $concrete = null, $shared = false);

	/**
	 * Register a shared binding in the container.
	 *
	 * @param  string  $abstract
	 * @param  \Closure|string|null  $concrete
	 * @return void
	 */
	public function singleton($abstract, $concrete = null);

	/**
	 * Resolve the given type from the container.
	 *
	 * @param  string  $abstract
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function make($abstract, $parameters = array());

}