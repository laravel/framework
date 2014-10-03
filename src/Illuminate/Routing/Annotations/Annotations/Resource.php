<?php namespace Illuminate\Routing\Annotations\Annotations;

use ReflectionClass;
use Illuminate\Support\Collection;
use Illuminate\Routing\Annotations\MethodEndpoint;
use Illuminate\Routing\Annotations\ResourceEndpoint;
use Illuminate\Routing\Annotations\EndpointCollection;

/**
 * @Annotation
 */
class Resource extends Annotation {

	/**
	 * All of the resource controller methods.
	 *
	 * @var array
	 */
	protected $methods = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

	/**
	 * {@inheritdoc}
	 */
	public function modifyCollection(EndpointCollection $endpoints, ReflectionClass $class)
	{
		$endpoints->push(new ResourceEndpoint([
			'reflection' => $class, 'name' => $this->value, 'names' => (array) $this->names,
			'only' => (array) $this->only, 'except' => (array) $this->except,
			'before' => $this->getBeforeFilters($endpoints), 'after' => $this->getAfterFilters($endpoints),
		]));
	}

	/**
	 * Get all of the pathless before filters.
	 *
	 * @param  EndpointCollection  $endpoints
	 * @return array
	 */
	protected function getBeforeFilters(EndpointCollection $endpoints)
	{
		return $this->getFilters($endpoints, 'pathlessBefore');
	}

	/**
	 * Get all of the pathless after filters.
	 *
	 * @param  EndpointCollection  $endpoints
	 * @return array
	 */
	protected function getAfterFilters(EndpointCollection $endpoints)
	{
		return $this->getFilters($endpoints, 'pathlessAfter');
	}

	/**
	 * Get all of the pathless filters for the given key.
	 *
	 * @param  EndpointCollection  $endpoints
	 * @param  string  $key
	 * @return array
	 */
	protected function getFilters(EndpointCollection $endpoints, $key)
	{
		$filters = [
			'index' => [], 'create' => [], 'store' => [], 'show' => [],
			'edit' => [], 'update' => [], 'destroy' => []
		];

		foreach ($this->getPathlessFilterEndpoints($endpoints, $key) as $endpoint)
		{
			$filters[$endpoint->method] = array_merge($filters[$endpoint->method], $endpoint->{$key});
		}

		return $filters;
	}

	/**
	 * Get all of the resource method endpoints with pathless filters.
	 *
	 * @param  EndpointCollection  $endpoints
	 * @param  string  $key
	 * @return array
	 */
	protected function getPathlessFilterEndpoints(EndpointCollection $endpoints, $key)
	{
		return Collection::make($endpoints)->filter(function($endpoint) use ($key)
		{
			return ($endpoint instanceof MethodEndpoint &&
	                in_array($endpoint->method, $this->methods) &&
	                count($endpoint->{$key}) > 0);

		})->all();
	}


}
