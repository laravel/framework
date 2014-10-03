<?php namespace Illuminate\Routing\Annotations\Annotations;

use Illuminate\Support\Collection;
use Illuminate\Routing\Annotations\ResourceEndpoint;
use Illuminate\Routing\Annotations\EndpointInterface;
use Illuminate\Routing\Annotations\EndpointCollection;

abstract class Filter extends Annotation {

	/**
	 * Apply the filters to the endpoints.
	 *
	 * @param  EndpointCollection  $endpoints
	 * @param  string  $key
	 * @return void
	 */
	protected function apply(EndpointCollection $endpoints, $key)
	{
		foreach ($endpoints as $endpoint)
		{
			$this->applyToEndpoint($endpoint, $key);
		}
	}

	/**
	 * Apply the annotation to the given endpoint.
	 *
	 * @param  EndpointInterface  $endpoint
	 * @param  string  $key
	 * @return void
	 */
	protected function applyToEndpoint(EndpointInterface $endpoint, $key)
	{
		if ( ! $endpoint->hasPaths())
			$this->mergeFilters($endpoint, 'pathless'.ucfirst($key), (array) $this->value);

		foreach ($this->getApplicablePaths($endpoint) as $path)
			$this->mergeFilters($path, $key, (array) $this->value);
	}

	/**
	 * Merge the filters into a given target's key.
	 *
	 * @param  mixed  $target
	 * @param  string  $key
	 * @param  array  $filters
	 * @return void
	 */
	protected function mergeFilters($target, $key, array $filters)
	{
		$target->{$key} = array_unique(array_merge($filters, $target->{$key}));
	}

	/**
	 * Get all of the applicable paths for the annotation.
	 *
	 * @param  EndpointInterface  $endpoint
	 * @return array
	 */
	protected function getApplicablePaths(EndpointInterface $endpoint)
	{
		return Collection::make($endpoint->getPaths())->filter(function($path) use ($endpoint)
		{
			$method = $endpoint->getMethodForPath($path);

			return ! $this->methodIsExcluded($method) && ! $this->verbIsExcluded($path->verb);

		})->all();
	}

	/**
	 * Determine if the given method name is excluded by the filter.
	 *
	 * @param  string  $methodName
	 * @return bool
	 */
	protected function methodIsExcluded($methodName)
	{
		if ($this->only && in_array($methodName, $this->only)) return false;

		return $this->only || ($this->except && in_array($methodName, $this->except));
	}

	/**
	 * Determine if the given HTTP verb is excluded by the filter.
	 *
	 * @param  string  $verb
	 * @return bool
	 */
	protected function verbIsExcluded($verb)
	{
		return $this->on && ! in_array($verb, $this->on);
	}

}
