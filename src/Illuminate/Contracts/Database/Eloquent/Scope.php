<?php namespace Illuminate\Contracts\Database\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface Scope {

	/**
	 * Apply the scope to a given Eloquent query builder.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $builder
	 * @param  \Illuminate\Database\Eloquent\Model  $model
	 * @return void
	 */
	public function apply(Builder $builder, Model $model);

	/**
	 * Remove the scope from the given Eloquent query builder.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $builder
	 * @param  \Illuminate\Database\Eloquent\Model  $model
	 *
	 * @return void
	 */
	public function remove(Builder $builder, Model $model);

}
