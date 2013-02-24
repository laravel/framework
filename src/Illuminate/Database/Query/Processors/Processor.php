<?php namespace Illuminate\Database\Query\Processors;

use Illuminate\Database\Query\Builder;

class Processor {

	/**
	 * Process the results of a "select" query.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  array  $results
	 * @return array
	 */
	public function processSelect(Builder $query, $results)
	{
		return $results;
	}

	/**
	 * Process an  "insert get ID" query.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  string  $sql
	 * @param  array   $values
	 * @param  string  $sequence
	 * @return int
	 */
	public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
	{
		$query->getConnection()->insert($sql, $values);

		// If the table has an incrementing primary key, we will
		// receive the ID back as the next incremental value of
		// the primary key. However, sometimes we won't have
		// incrementing primary keys, in which case casting as
		// an ID would return 0.
		if (is_numeric($id = $query->getConnection()->getPdo()->lastInsertId($sequence)))
		{
			return (int) $id;
		}

		return $id;
	}

}
