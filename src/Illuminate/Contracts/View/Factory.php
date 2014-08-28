<?php namespace Illuminate\Contracts\View;

interface Factory {

	/**
	 * Determine if a given view exists.
	 *
	 * @param  string  $view
	 * @return bool
	 */
	public function exists($view);

	/**
	 * Get the evaluated view contents for the given view.
	 *
	 * @param  string  $view
	 * @param  array   $data
	 * @param  array   $mergeData
	 * @return \Illuminate\View\View
	 */
	public function make($view, $data = array(), $mergeData = array());

	/**
	 * Register a view composer event.
	 *
	 * @param  array|string  $views
	 * @param  \Closure|string  $callback
	 * @param  int|null  $priority
	 * @return array
	 */
	public function composer($views, $callback, $priority = null);

	/**
	 * Register a view creator event.
	 *
	 * @param  array|string     $views
	 * @param  \Closure|string  $callback
	 * @return array
	 */
	public function creator($views, $callback);

	/**
	 * Add a new namespace to the loader.
	 *
	 * @param  string  $namespace
	 * @param  string|array  $hints
	 * @return void
	 */
	public function addNamespace($namespace, $hints);

}
