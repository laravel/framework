<?php namespace Illuminate\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;

class SQLiteGrammar extends Grammar {

	/**
	 * All of the available clause operators.
	 *
	 * @var array
	 */
	protected $operators = array(
		'=', '<', '>', '<=', '>=', '<>', '!=',
		'like', 'not like', 'between', 'ilike',
		'&', '|', '<<', '>>',
	);

	/**
	 * Compile an insert statement into SQL.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $values
	 * @return string
	 */
	public function compileInsert(Builder $query, array $values)
	{
		// Essentially we will force every insert to be treated as a batch insert which
		// simply makes creating the SQL easier for us since we can utilize the same
		// basic routine regardless of an amount of records given to us to insert.
		$table = $this->wrapTable($query->from);

		if ( ! is_array(reset($values)))
		{
			$values = array($values);
		}

		// If there is only one record being inserted, we will just use the usual query
		// grammar insert builder because no special syntax is needed for the single
		// row inserts in SQLite. However, if there are multiples, we'll continue.
		if (count($values) == 1)
		{
			return parent::compileInsert($query, $values[0]);
		}

		$names = $this->columnize(array_keys($values[0]));

		$columns = array();

		// SQLite requires us to build the multi-row insert as a listing of select with
		// unions joining them together. So we'll build out this list of columns and
		// then join them all together with select unions to complete the queries.
		foreach (array_keys($values[0]) as $column)
		{
			$columns[] = '? as '.$this->wrap($column);
		}

		$columns = array_fill(0, count($values), implode(', ', $columns));

		return "insert into $table ($names) select ".implode(' union select ', $columns);
	}

	/**
	 * Compile a truncate table statement into SQL.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @return array
	 */
	public function compileTruncate(Builder $query)
	{
		$sql = array('delete from sqlite_sequence where name = ?' => array($query->from));

		$sql['delete from '.$this->wrapTable($query->from)] = array();

		return $sql;
	}

}
