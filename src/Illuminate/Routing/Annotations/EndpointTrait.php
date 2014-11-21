<?php namespace Illuminate\Routing\Annotations;

trait EndpointTrait {

	/**
	 * Determine if the middleware applies to a given method.
	 *
	 * @param  string  $method
	 * @param  array  $middleware
	 * @return bool
	 */
	protected function middlewareAppliesToMethod($method, array $middleware)
	{
		if ( ! empty($middleware['only']) && ! in_array($method, $middleware['only']))
		{
			return false;
		}
		elseif ( ! empty($middleware['except']) && in_array($method, $middleware['except']))
		{
			return false;
		}

		return true;
	}

	/**
	 * Get the controller method for the given endpoint path.
	 *
	 * @param  \Illuminate\Routing\Annotations\AbstractPath  $path
	 * @return string
	 */
	public function getMethodForPath(AbstractPath $path)
	{
		return $path->method;
	}

	/**
	 * Add the given path definition to the endpoint.
	 *
	 * @param  \Illuminate\Routing\Annotations\AbstractPath  $path
	 * @return void
	 */
	public function addPath(AbstractPath $path)
	{
		$this->paths[] = $path;
	}

	/**
	 * Implode the given list into a comma separated string.
	 *
	 * @param  array  $list
	 * @return string
	 */
	protected function implodeArray(array $array)
	{
		$results = [];

		foreach ($array as $key => $value)
		{
			if (is_string($key))
			{
				$results[] = "'".$key."' => '".$value."'";
			}
			else
			{
				$results[] = "'".$value."'";
			}
		}

		return count($results) > 0 ? implode(', ', $results) : '';
	}

}
