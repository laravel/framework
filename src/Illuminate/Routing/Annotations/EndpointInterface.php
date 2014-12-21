<?php namespace Illuminate\Routing\Annotations;

interface EndpointInterface {

	/**
	 * Transform the endpoint into a route definition.
	 *
	 * @return string
	 */
	public function toRouteDefinition();

	/**
	 * Determine if the endpoint has any paths.
	 *
	 * @var bool
	 */
	public function hasPaths();

	/**
	 * Get all of the path definitions for an endpoint.
	 *
	 * @return array
	 */
	public function getPaths();

	/**
	 * Add the given path definition to the endpoint.
	 *
	 * @param  \Illuminate\Routing\Annotations\AbstractPath  $path
	 * @return void
	 */
	public function addPath(AbstractPath $path);

	/**
	 * Get the controller method for the given endpoint path.
	 *
	 * @param  \Illuminate\Routing\Annotations\AbstractPath  $path
	 * @return string
	 */
	public function getMethodForPath(AbstractPath $path);

}
