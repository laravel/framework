<?php namespace Illuminate\Foundation\Console\Tinker\Presenters;

use Psy\Presenter\ArrayPresenter;
use Illuminate\Support\Collection;

class IlluminateCollectionPresenter extends ArrayPresenter {

	/**
	 * Determine if the presenter can present the given value.
	 *
	 * @param  mixed  $value
	 * @return bool
	 */
	public function canPresent($value)
	{
		return $value instanceof Collection;
	}

	/**
	 * Determine if the given value is a collection.
	 *
	 * @param  object  $value
	 * @return bool
	 */
	protected function isArrayObject($value)
	{
		return $value instanceof Collection;
	}

	/**
	 * Get an array of collection values.
	 *
	 * @param  object  $value
	 * @return array
	 */
	protected function getArrayObjectValue($value)
	{
		return $value->all();
	}

}
