<?php namespace Illuminate\Database\Query\Processors;

use Illuminate\Database\Query\Builder;

class MySqlProcessor extends Processor {

	/**
	 * Process the results of a column listing query.
	 *
	 * @param  array  $results
	 * @return array
	 */
	public function processColumnListing($results)
	{
		return array_map(function($r) { return $r->column_name; }, $results);
	}

	/**
	 * Process the results of a column description query.
	 *
	 * @param  object $results
	 * @return array
	 */
	public function processColumnType($results)
	{
		return array(
			'type'     => $results[0]->Type,
			'nullable' => $results[0]->Null === 'YES',
			'default'  => $results[0]->Default === 'NULL' ? null : $results[0]->Default,
			'extra'    => $results[0]->Extra
		);
	}

}
