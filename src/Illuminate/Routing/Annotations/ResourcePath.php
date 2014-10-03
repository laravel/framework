<?php namespace Illuminate\Routing\Annotations;

class ResourcePath extends AbstractPath {

	/**
	 * The controller method of the resource path.
	 *
	 * @param  string  $method
	 */
	public $method;

	/**
	 * Create a new Resource Path instance.
	 *
	 * @param  string  $method
	 * @return void
	 */
	public function __construct($method)
	{
		$this->method = $method;
		$this->verb = $this->getVerb($method);
	}

	/**
	 * Get the verb for the given resource method.
	 *
	 * @param  string  $method
	 * @return string
	 */
	protected function getVerb($method)
	{
		switch ($method)
		{
			case 'index':
			case 'create':
			case 'show':
			case 'edit':
				return 'get';

			case 'store':
				return 'post';

			case 'update':
				return 'put';

			case 'destroy':
				return 'delete';
		}
	}

}
