<?php namespace Illuminate\Routing\Annotations;

abstract class AbstractPath {

	/**
	 * The HTTP verb the route responds to.
	 *
	 * @var array
	 */
	public $verb;

	/**
	 * The domain the route responds to.
	 *
	 * @var string
	 */
	public $domain;

	/**
	 * The path / URI the route responds to.
	 *
	 * @var string
	 */
	public $path;

	/**
	 * The path's middleware.
	 *
	 * @var array
	 */
	public $middleware = [];

	/**
	 * The path's "where" clauses.
	 *
	 * @var array
	 */
	public $where = [];

}
