<?php namespace Illuminate\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;

class MySqlGrammar extends Grammar {

	/**
	 * The keyword identifier wrapper format.
	 *
	 * @var string
	 */
	protected $wrapper = '`%s`';

	/**
	 * Compile the lock for this query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  bool  $update
	 * @return string
	 */
	public function compileLock(Builder $query, $update)
	{
		return $update ? 'for update' : 'lock in share mode';
	}

}