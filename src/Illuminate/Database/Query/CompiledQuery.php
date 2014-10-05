<?php namespace Illuminate\Database\Query;

class CompiledQuery {

	/**
	 * The sql query string.
	 *
	 * @var string
	 */
	public $sql;

	/**
	 * The query value bindings.
	 *
	 * @var array
	 */
	public $bindings;

	/**
	 * Create a new compile query
	 *
	 * @param string  $sql
	 * @param array  $bindings
	 */
	public function __construct($sql = '', $bindings = [])
	{
		$this->sql = $sql;
		$this->bindings = $bindings;
	}

	/**
	 * Concatenate this compiled query into this query by joining the sql strings and merging the binding arrays.
	 *
	 * @param CompiledQuery  $compiled
	 * @return CompiledQuery|static
	 */
	public function concatenate(CompiledQuery $compiled = null, $glue = ' ')
	{
		if (!is_null($compiled)) {
			$this->sql = trim(implode($glue, array_filter([trim($this->sql), trim($compiled->sql)], function ($value) {
				return (string)$value !== '';
			})));

			$this->bindings = array_merge((array)$this->bindings, (array)$compiled->bindings);
		}

		return $this;
	}

} 