<?php namespace Illuminate\Database\Query\Processors;

use Illuminate\Database\Query\Builder;

class PostgresProcessor extends Processor {

	/**
	 * Process an "insert get ID" query.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  string  $sql
	 * @param  array   $values
	 * @param  string  $sequence
	 * @return int
	 */
	public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
	{
		$results = $query->getConnection()->select($sql, $values);

		$sequence = $sequence ?: 'id';

		return $results[0]->$sequence;
	}

}