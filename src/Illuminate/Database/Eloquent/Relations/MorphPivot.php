<?php namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;

class MorphPivot extends Pivot {

	/**
	 * Set the keys for a save update query.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $query
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	protected function setKeysForSaveQuery(Builder $query)
	{
		$query->where($this->morphType, $this->getAttribute($this->morphType));

		return parent::setKeysForSaveQuery($query);
	}

	/**
	 * Delete the pivot model record from the database.
	 *
	 * @return int
	 */
	public function delete()
	{
		$query = $this->getDeleteQuery();

		$query->where($this->morphType, $this->getAttribute($this->morphType));

		return $query->delete();
	}

	/**
	 * Set the morph type for the pivot.
	 *
	 * @param  string  $morphType
	 * @return \Illuminate\Database\Eloquent\Relations\MorphPivot
	 */
	public function setMorphType($morphType)
	{
		$this->morphType = $morphType;

		return $this;
	}

}
