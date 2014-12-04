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
	 * Get the evaluated view contents for the given path.
	 *
	 * @param  string  $path
	 * @param  array  $data
	 * @param  array  $mergeData
	 * @return \Illuminate\Contracts\View\View
	 */
	public function file($path, $data = array(), $mergeData = array());

	/**
	 * Get the evaluated view contents for the given view.
	 *
	 * @param  string  $view
	 * @param  array  $data
	 * @param  array  $mergeData
	 * @return \Illuminate\Contracts\View\View
	 */
	public function make($view, $data = array(), $mergeData = array());

	/**
	 * Add a piece of shared data to the environment.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function share($key, $value = null);

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
	 * @param  array|string  $views
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
