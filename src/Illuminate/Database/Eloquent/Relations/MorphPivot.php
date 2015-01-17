<?php namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;

class MorphPivot extends Pivot {

	/**
	 * The type of the polymorphic relation.
	 *
	 * Explicitly define this so it's not included in saved attributes.
	 *
	 * @var string
	 */
	protected $morphType;

	/**
	 * The value of the polymorphic relation.
	 *
	 * Explicitly define this so it's not included in saved attributes.
	 *
	 * @var string
	 */
	protected $morphClass;

	/**
	 * Set the keys for a save update query.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $query
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	protected function setKeysForSaveQuery(Builder $query)
	{
		$query->where($this->morphType, $this->morphClass);

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

		$query->where($this->morphType, $this->morphClass);

		return $query->delete();
	}

	/**
	 * Set the morph type for the pivot.
	 *
	 * @param  string  $morphType
	 * @return $this
	 */
	public function setMorphType($morphType)
	{
		$this->morphType = $morphType;

		return $this;
	}

	/**
	 * Set the morph class for the pivot.
	 *
	 * @param  string  $morphClass
	 * @return \Illuminate\Database\Eloquent\Relations\MorphPivot
	 */
	public function setMorphClass($morphClass)
	{
		$this->morphClass = $morphClass;

		return $this;
	}

}
