<?php namespace Illuminate\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\CompiledQuery;
use Illuminate\Database\Grammar as BaseGrammar;


class Grammar extends BaseGrammar {

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
		'unions',
		'lock',
	);

	/**
	 * Compile a select query into SQL.
	 *
	 * @param  \Illuminate\Database\Query\Builder
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	public function compileSelect(Builder $query)
	{
		if (is_null($query->columns)) $query->setSelect(array('*'));

		return $this->concatenate($this->compileComponents($query));
	}

	/**
	 * Compile the components necessary for a select clause.
	 *
	 * @param  \Illuminate\Database\Query\Builder
	 * @return array
	 */
	protected function compileComponents(Builder $query)
	{
		$compiledComponents = array();

		foreach ($this->selectComponents as $component)
		{
			// To compile the query, we'll spin through each component of the query and
			// see if that component exists. If it does we'll just call the compiler
			// function for the component which is responsible for making the SQL.
			if ( ! is_null($query->$component))
			{
				$method = 'compile'.ucfirst($component);

				$compiledComponents[$component] = $this->$method($query, $query->$component);
			}
		}

		return $compiledComponents;
	}

	/**
	 * Compile an aggregated select clause.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $aggregate
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function compileAggregate(Builder $query, $aggregate)
	{
		$column = $this->columnize($aggregate['columns']);

		// If the query has a "distinct" constraint and we're not asking for all columns
		// we need to prepend "distinct" onto the column name so that the query takes
		// it into account when it performs the aggregating operations on the data.
		if ($query->distinct && $column !== '*')
		{
			$column = 'distinct '.$column;
		}

		return new CompiledQuery('select '.$aggregate['function'].'('.$column.') as aggregate');
	}

	/**
	 * Compile the "select *" portion of the query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $columns
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function compileColumns(Builder $query, $columns)
	{
		// If the query is actually performing an aggregating select, we will let that
		// compiler handle the building of the select clauses, as it will need some
		// more syntax that is best handled by that function to keep things neat.
		if ( ! is_null($query->aggregate)) return;

		$select = $query->distinct ? 'select distinct ' : 'select ';

		$bindings = array_flatten(array_fetch($columns, 'bindings'));

		$columns = array_flatten(array_fetch($columns, 'columns'));

		return new CompiledQuery($select.$this->columnize($columns), $bindings);
	}

	/**
	 * Compile the "from" portion of the query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  string  $table
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function compileFrom(Builder $query, $table)
	{
		return new CompiledQuery('from '.$this->wrapTable($table));
	}

	/**
	 * Compile the "join" portions of the query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $joins
	 * @return string
	 */
	protected function compileJoins(Builder $query, $joins)
	{
		$complied = new CompiledQuery;

		foreach ($joins as $join)
		{
			$table = $this->wrapTable($join->table);
			$type = $join->type;

			$compiledJoin = new CompiledQuery("$type join $table on", $join->bindings);

			// First we need to build all of the "on" clauses for the join. There may be many
			// of these clauses so we will need to iterate through each one and build them
			// separately, then we'll join them up into a single string when we're done.
			$compiledClauses = new CompiledQuery;

			foreach ($join->clauses as $clause)
			{
				$compiledClauses->concatenate($this->compileJoinConstraint($clause));
			}

			// Once we have constructed the clauses, we'll need to take the boolean connector
			// off of the first clause as it obviously will not be required on that clause
			// because it leads the rest of the clauses, thus not requiring any boolean.
			$compiledClauses->sql = $this->removeLeadingBoolean($compiledClauses->sql);

			$compiledJoin->concatenate($compiledClauses);

			// Once we have everything ready to go, we will just concatenate all the parts to
			// build the final join statement SQL for the query and we can then return the
			// final clause back to the callers as a single, stringified join statement.
			$complied->concatenate($compiledJoin);
		}

		return $complied;
	}

	/**
	 * Create a join clause constraint segment.
	 *
	 * @param  array   $clause
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function compileJoinConstraint(array $clause)
	{
		$first = $this->wrap($clause['first']);

		$second = $clause['where'] ? '?' : $this->wrap($clause['second']);

		return new CompiledQuery("{$clause['boolean']} $first {$clause['operator']} $second");
	}

	/**
	 * Compile the "where" portions of the query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function compileWheres(Builder $query)
	{
		if (is_null($query->wheres)) return;

		$compiledWheres = new CompiledQuery;

		// Each type of where clauses has its own compiler function which is responsible
		// for actually creating the where clauses SQL. This helps keep the code nice
		// and maintainable since each clause has a very small method that it uses.
		foreach ($query->wheres as $where)
		{
			$method = "where{$where['type']}";

			$compiledWheres->concatenate(new CompiledQuery($where['boolean']))->concatenate($this->$method($query, $where));
		}

		// If we actually have some where clauses, we will strip off the first boolean
		// operator, which is added by the query builders for convenience so we can
		// avoid checking for the first clauses in each of the compilers methods.
		if (count($compiledWheres->sql) > 0)
		{
			$compiledWheres->sql = 'where '.$this->removeLeadingBoolean($compiledWheres->sql);

			return $compiledWheres;
		}

		return new CompiledQuery;
	}

	/**
	 * Compile a nested where clause.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $where
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function whereNested(Builder $query, $where)
	{
		$nested = $where['query'];

		$wheres = $this->compileWheres($nested);

		// strip off the first 'wheres '
		$wheres->sql = '('. substr($wheres->sql, 6). ')';

		return $wheres;
	}

	/**
	 * Compile a where condition with a sub-select.
	 *
	 * @param  \Illuminate\Database\Query\Builder $query
	 * @param  array   $where
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function whereSub(Builder $query, $where)
	{
		$select = $this->compileSelect($where['query']);

		$select->sql = $this->wrap($where['column']).' '.$where['operator']." ($select->sql)";

		return $select;
	}

	/**
	 * Compile a basic where clause.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $where
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function whereBasic(Builder $query, $where)
	{
		$value = $where['value'];
		$bindings = ! $value instanceof Expression ? [$value] : null;

		$value = $this->parameter($value);

		$sql = $this->wrap($where['column']).' '.$where['operator'].' '.$value;

		return new CompiledQuery($sql, $bindings);
	}

	/**
	 * Compile a "between" where clause.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $where
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function whereBetween(Builder $query, $where)
	{
		$between = $where['not'] ? 'not between' : 'between';

		$sql = $this->wrap($where['column']).' '.$between.' ? and ?';

		return new CompiledQuery($sql, $where['values']);
	}

	/**
	 * Compile a where exists clause.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $where
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function whereExists(Builder $query, $where)
	{
		$select = $this->compileSelect($where['query']);

		$select->sql = "exists ($select->sql)";

		return $select;
	}

	/**
	 * Compile a where exists clause.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $where
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function whereNotExists(Builder $query, $where)
	{
		$select = $this->compileSelect($where['query']);

		$select->sql = "not exists ($select->sql)";

		return $select;
	}

	/**
	 * Compile a "where in" clause.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $where
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function whereIn(Builder $query, $where)
	{
		$values = $this->parameterize($where['values']);

		$sql = $this->wrap($where['column']).' in ('.$values.')';

		return new CompiledQuery($sql, $where['values']);
	}

	/**
	 * Compile a "where not in" clause.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $where
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function whereNotIn(Builder $query, $where)
	{
		$values = $this->parameterize($where['values']);

		$sql = $this->wrap($where['column']).' not in ('.$values.')';

		return new CompiledQuery($sql, $where['values']);
	}

	/**
	 * Compile a where in sub-select clause.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $where
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function whereInSub(Builder $query, $where)
	{
		$select = $this->compileSelect($where['query']);

		$select->sql = $this->wrap($where['column']).' in ('.$select->sql.')';

		return $select;
	}

	/**
	 * Compile a where not in sub-select clause.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $where
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function whereNotInSub(Builder $query, $where)
	{
		$select = $this->compileSelect($where['query']);

		$select->sql = $this->wrap($where['column']).' not in ('.$select->sql.')';

		return $select;
	}

	/**
	 * Compile a "where null" clause.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $where
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function whereNull(Builder $query, $where)
	{
		return new CompiledQuery($this->wrap($where['column']).' is null');
	}

	/**
	 * Compile a "where not null" clause.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $where
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function whereNotNull(Builder $query, $where)
	{
		return new CompiledQuery($this->wrap($where['column']).' is not null');
	}

	/**
	 * Compile a "where day" clause.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $where
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function whereDay(Builder $query, $where)
	{
		return $this->dateBasedWhere('day', $query, $where);
	}

	/**
	 * Compile a "where month" clause.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $where
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function whereMonth(Builder $query, $where)
	{
		return $this->dateBasedWhere('month', $query, $where);
	}

	/**
	 * Compile a "where year" clause.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $where
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function whereYear(Builder $query, $where)
	{
		return $this->dateBasedWhere('year', $query, $where);
	}

	/**
	 * Compile a date based where clause.
	 *
	 * @param  string  $type
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $where
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function dateBasedWhere($type, Builder $query, $where)
	{
		$value = $this->parameter($where['value']);

		$sql = $type.'('.$this->wrap($where['column']).') '.$where['operator'].' '.$value;

		return new CompiledQuery($sql, $where['value']);
	}

	/**
	 * Compile a raw where clause.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $where
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function whereRaw(Builder $query, $where)
	{
		return new CompiledQuery($where['sql'], $where['bindings']);
	}

	/**
	 * Compile the "group by" portions of the query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $groups
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function compileGroups(Builder $query, $groups)
	{
		return new CompiledQuery('group by '.$this->columnize($groups));
	}

	/**
	 * Compile the "having" portions of the query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $havings
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function compileHavings(Builder $query, $havings)
	{
		$compiled = new CompiledQuery('having');

		$compiledHavings = new CompiledQuery;

		foreach ($havings as $having)
		{
			$compiledHavings->concatenate($this->compileHaving($query, $having));
		}

		$compiledHavings->sql = preg_replace('/and /', '', $compiledHavings->sql, 1);

		return $compiled->concatenate($compiledHavings);
	}

	/**
	 * Compile a single having clause.
	 *
	 * @param  array   $having
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function compileHaving(Builder $query, array $having)
	{
		// If the having clause is "raw", we can just return the clause straight away
		// without doing any more processing on it. Otherwise, we will compile the
		// clause into SQL based on the components that make it up from builder.
		if ($having['type'] === 'raw')
		{
			return new CompiledQuery($having['boolean'].' '.$having['sql'], $having['bindings']);
		}

		return $this->compileBasicHaving($query, $having);
	}

	/**
	 * Compile a basic having clause.
	 *
	 * @param  array   $having
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function compileBasicHaving(Builder $query, $having)
	{
		$column = $this->wrap($having['column']);

		$parameter = $this->parameter($having['value']);

		return new CompiledQuery($having['boolean'].' '.$column.' '.$having['operator'].' '.$parameter, $having['value']);
	}

	/**
	 * Compile the "order by" portions of the query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $orders
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function compileOrders(Builder $query, $orders)
	{
		$compiled = new CompiledQuery('order by');

		$compiledOrders = new CompiledQuery;

		foreach($orders as $order)
		{
			if (isset($order['sql']))
			{
				$compiledOrder = new CompiledQuery($order['sql'], $order['bindings']);
			}
			else
			{
				$compiledOrder = new CompiledQuery($this->wrap($order['column']).' '.$order['direction']);
			}

			$compiledOrders->concatenate($compiledOrder, ', ');
		}

		return $compiled->concatenate($compiledOrders);
	}

	/**
	 * Compile the "limit" portions of the query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  int  $limit
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function compileLimit(Builder $query, $limit)
	{
		return new CompiledQuery('limit '.(int) $limit);
	}

	/**
	 * Compile the "offset" portions of the query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  int  $offset
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function compileOffset(Builder $query, $offset)
	{
		return new CompiledQuery('offset '.(int) $offset);
	}

	/**
	 * Compile the "union" queries attached to the main query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function compileUnions(Builder $query, $unions)
	{
		$compiled = new CompiledQuery;

		foreach ($unions as $union)
		{
			$compiled->concatenate($this->compileUnion($union));
		}

		return $compiled;
	}

	/**
	 * Compile a single union statement.
	 *
	 * @param  array  $union
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function compileUnion(array $union)
	{
		$joiner = $union['all'] ? 'union all' : 'union';

		$compiled = new CompiledQuery($joiner);

		return $compiled->concatenate($this->compileSelect($union['query']));
	}

	/**
	 * Compile an insert statement into SQL.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $values
	 * @return \Illuminate\Database\Query\CompiledQuery
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

		$columns = $this->columnize(array_keys(reset($values)));

		// We need to build a list of parameter place-holders of values that are bound
		// to the query. Each insert should have the exact same amount of parameter
		// bindings so we can just go off the first list of values in this array.
		$parameters = $this->parameterize(reset($values));

		$value = array_fill(0, count($values), "($parameters)");

		$parameters = implode(', ', $value);

		return new CompiledQuery("insert into $table ($columns) values $parameters");
	}

	/**
	 * Compile an insert and get ID statement into SQL.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array   $values
	 * @param  string  $sequence
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	public function compileInsertGetId(Builder $query, $values, $sequence)
	{
		return $this->compileInsert($query, $values);
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
		$table = $this->wrapTable($query->from);

		$compiled = new CompiledQuery("update {$table}");

		// If the query has any "join" clauses, we will setup the joins on the builder
		// and compile them so we can attach them to this update, as update queries
		// can get join statements to attach to other tables when they're needed.

		$joins = isset($query->joins) ? $this->compileJoins($query, $query->joins) : null;
		$compiled->concatenate($joins);

		// Each one of the columns in the update statements needs to be wrapped in the
		// keyword identifiers, also a place-holder needs to be created for each of
		// the values in the list of bindings so we can make the sets statements.
		$columns = array();

		foreach ($values as $key => $value)
		{
			$columns[] = $this->wrap($key).' = '.$this->parameter($value);
		}

		$columns = implode(', ', $columns);

		$compiled->concatenate(new CompiledQuery("set $columns"));

		// Of course, update queries may also be constrained by where clauses so we'll
		// need to compile the where clauses and attach it to the query so only the
		// intended records are updated by the SQL statements we generate to run.
		$where = $this->compileWheres($query);
		$compiled->concatenate($where);

		return $compiled;
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

		$compiled = new CompiledQuery("delete from $table");

		$where = is_array($query->wheres) ? $this->compileWheres($query) : null;

		return $compiled->concatenate($where);
	}

	/**
	 * Compile a truncate table statement into SQL.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @return array
	 */
	public function compileTruncate(Builder $query)
	{
		return array('truncate '.$this->wrapTable($query->from) => array());
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
		return new CompiledQuery(is_string($value) ? $value : '');
	}

	/**
	 * Concatenate an array of segments, removing empties.
	 *
	 * @param  array   $segments
	 * @return \Illuminate\Database\Query\CompiledQuery
	 */
	protected function concatenate($segments)
	{
		$compiled = new CompiledQuery;

		foreach($segments as $key => $segment)
		{
			$compiled->concatenate($segment);
		}

		return $compiled;
	}

	/**
	 * Remove the leading boolean from a statement.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function removeLeadingBoolean($value)
	{
		return preg_replace('/and |or /', '', $value, 1);
	}

}
