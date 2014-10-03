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
	 * @param  array  $before
	 * @param  array  $after
	 * @param  array  $where
	 * @return void
	 */
	public function __construct($verb, $domain, $path, $as, $before = [], $after = [], $where = [])
	{
		$this->as = $as;
		$this->verb = $verb;
		$this->after = $after;
		$this->where = $where;
		$this->domain = $domain;
		$this->before = $before;
		$this->path = $path == '/' ? '/' : trim($path, '/');
	}

}
