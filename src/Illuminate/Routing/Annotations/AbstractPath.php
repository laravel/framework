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
	 * The path's before filters.
	 *
	 * @var array
	 */
	public $before = [];

	/**
	 * The path's after filters.
	 *
	 * @var array
	 */
	public $after = [];

	/**
	 * The path's "where" clauses.
	 *
	 * @var array
	 */
	public $where = [];

}
