<?php namespace Illuminate\Database\Eloquent;

interface ScopeInterface {

	/**
	 * Apply the scope to a given Eloquent query builder.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $builder
	 * @return void
	 */
	public function apply(Builder $builder);

	/**
	 * Remove the scope from the given Eloquent query builder.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $builder
	 * @return void
	 */
	public function remove(Builder $builder);

}
