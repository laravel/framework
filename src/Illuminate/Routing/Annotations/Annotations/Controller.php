<?php namespace Illuminate\Routing\Annotations\Annotations;

use ReflectionClass;
use Illuminate\Routing\Annotations\EndpointCollection;

/**
 * @Annotation
 */
class Controller extends Annotation {

	/**
	 * {@inheritdoc}
	 */
	public function modifyCollection(EndpointCollection $endpoints, ReflectionClass $class)
	{
		if ($this->prefix) $this->prefixEndpoints($endpoints);

		if ($this->domain) $this->setEndpointDomains($endpoints);
	}

	/**
	 * Set the prefixes on the endpoints.
	 *
	 * @param  EndpointCollection  $endpoints
	 * @return void
	 */
	protected function prefixEndpoints(EndpointCollection $endpoints)
	{
		foreach ($endpoints->getAllPaths() as $path)
		{
			$path->path = $this->trimPath($this->prefix, $path->path);
		}
	}

	/**
	 * Set the domain on the endpoints.
	 *
	 * @param  EndpointCollection  $endpoints
	 * @return void
	 */
	protected function setEndpointDomains(EndpointCollection $endpoints)
	{
		foreach ($endpoints->getAllPaths() as $path)
		{
			if (is_null($path->domain)) $path->domain = $this->domain;
		}
	}

	/**
	 * Trim the path slashes for a given prefix and path.
	 *
	 * @param  string  $prefix
	 * @param  string  $path
	 * @return string
	 */
	protected function trimPath($prefix, $path)
	{
		return trim(trim($prefix, '/').'/'.trim($path, '/'), '/');
	}

}
