<?php namespace Illuminate\Database\Query;

class JoinClause {

	/**
	 * The type of join being performed.
	 *
	 * @var string
	 */
	public $type;

	/**
	 * The table the join clause is joining to.
	 *
	 * @var string
	 */
	public $table;

	/**
	 * The "on" clauses for the join.
	 *
	 * @var array
	 */
	public $clauses = array();

	/**
	 * Create a new join clause instance.
	 *
	 * @param  string  $type
	 * @param  string  $table
	 * @return void
	 */
	public function __construct($type, $table)
	{
		$this->type = $type;
		$this->table = $table;
	}

	/**
	 * Add an "on" clause to the join.
	 *
	 * @param  string  $first
	 * @param  string  $operator
	 * @param  string  $second
	 * @param  string  $boolean
	 * @return \Illuminate\Database\Query\JoinClause
	 */
	public function on($first, $operator, $second, $boolean = 'and')
	{
		$this->clauses[] = compact('first', 'operator', 'second', 'boolean');

		return $this;
	}

	/**
	 * Add an "or on" clause to the join.
	 *
	 * @param  string  $first
	 * @param  string  $operator
	 * @param  string  $second
	 * @return \Illuminate\Database\Query\JoinClause
	 */
	public function orOn($first, $operator, $second)
	{
		return $this->on($first, $operator, $second, 'or');
	}

}