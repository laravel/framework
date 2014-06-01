<?php namespace Illuminate\Config;

interface LoaderInterface {

	/**
	 * Load the given configuration group.
	 *
	 * @param  string  $env
	 * @param  string  $group
	 * @param  string  $namespace
	 * @return array
	 */
	public function load($env, $group, $namespace = null);

	/**
	 * Determine if the given group exists.
	 *
	 * @param  string  $group
	 * @param  string  $namespace
	 * @return bool
	 */
	public function exists($group, $namespace = null);

	/**
	 * Apply any cascades to an array of package options.
	 *
	 * @param  string  $env
	 * @param  string  $package
	 * @param  string  $group
	 * @param  array   $items
	 * @return array
	 */
	public function cascadePackage($env, $package, $group, $items);

	/**
	 * Add a new namespace to the loader.
	 *
	 * @param  string  $namespace
	 * @param  string  $hint
	 * @return void
	 */
	public function addNamespace($namespace, $hint);

	/**
	 * Returns all registered namespaces with the config loader.
	 *
	 * @return array
	 */
	public function getNamespaces();

}
