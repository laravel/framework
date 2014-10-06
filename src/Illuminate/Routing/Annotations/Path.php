<?php namespace Illuminate\Routing\Annotations;

class Path extends AbstractPath {

	/**
	 * The name of the route.
	 *
	 * @var string
	 */
	public $as;

	/**
	 * Create a new Route Path instance.
	 *
	 * @param  string  $verb
	 * @param  string  $domain
	 * @param  string  $path
	 * @param  string  $as
	 * @param  array  $middleware
	 * @param  array  $where
	 * @return void
	 */
	public function __construct($verb, $domain, $path, $as, $middleware = [], $where = [])
	{
		$this->as = $as;
		$this->verb = $verb;
		$this->where = $where;
		$this->domain = $domain;
		$this->middleware = $middleware;
		$this->path = $path == '/' ? '/' : trim($path, '/');
	}

}
