<?php namespace Illuminate\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\CompiledQuery;

class MySqlGrammar extends Grammar {

	/**
	 * The components that make up a select clause.
	 *
	 * @var array
	 */
	protected $selectComponents = array(
		'aggregate',
		'columns',
		'from',
		'joins',
		'wheres',
		'groups',
		'havings',
		'orders',
		'limit',
		'offset',
		'lock',
	);

	/**
	 * Compile a select query into SQL.
	 *
	 * @param  \Illuminate\Database\Query\Builder
	 * @return string
	 */
	public function compileSelect(Builder $query)
	{
		$select = parent::compileSelect($query);

		if ($query->unions)
		{
			$select->sql = '('.$select->sql.')';
			$select->concatenate($this->compileUnions($query, $query->unions));
		}

		return $select;
	}

	/**
	 * Compile a single union statement.
	 *
	 * @param  array  $union
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function compileUnion(array $union)
	{
		$joiner = $union['all'] ? 'union all ' : 'union ';

		$select = $this->compileSelect($union['query']);

		$select->sql = "$joiner({$select->sql})";

		return $select;
	}

	/**
	 * Compile the lock into SQL.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  bool|string  $value
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function compileLock(Builder $query, $value)
	{
		if (is_string($value)) return CompiledQuery($value);

		return new CompiledQuery($value ? 'for update' : 'lock in share mode');
	}

	/**
	 * Compile an update statement into SQL.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $values
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	public function compileUpdate(Builder $query, $values)
	{
		$update = parent::compileUpdate($query, $values);

		if (isset($query->orders))
		{
			$update->concatenate($this->compileOrders($query, $query->orders));
		}

		if (isset($query->limit))
		{
			$update->concatenate($this->compileLimit($query, $query->limit));
		}

		return $update;
	}

	/**
	 * Compile a delete statement into SQL.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	public function compileDelete(Builder $query)
	{
		$table = $this->wrapTable($query->from);

		$where = is_array($query->wheres) ? $this->compileWheres($query) : null;

		if (isset($query->joins))
		{
			$joins = $this->compileJoins($query, $query->joins);

			$compiled = new CompiledQuery("delete $table from {$table}");

			return $compiled->concatenate($joins)->concatenate($where);
		}

		$compiled = new CompiledQuery("delete from $table");

		return $compiled->concatenate($where);
	}

	/**
	 * Wrap a single string in keyword identifiers.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function wrapValue($value)
	{
		if ($value === '*') return $value;

		return '`'.str_replace('`', '``', $value).'`';
	}

}
