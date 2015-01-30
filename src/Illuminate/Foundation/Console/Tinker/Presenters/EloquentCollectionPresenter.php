<?php namespace Illuminate\Foundation\Console\Tinker\Presenters;

use Psy\Presenter\ArrayPresenter;
use Illuminate\Database\Eloquent\Collection;

class EloquentCollectionPresenter extends ArrayPresenter {

	/**
	 * EloquentCollectionPresenter can present Collections.
	 *
	 * @param  mixed  $value
	 * @return bool
	 */
	public function canPresent($value)
	{
		return $value instanceof Collection;
	}

    /**
     * Collections should be treated as ArrayObjects.
     *
	 * @param  object  $value
     * @return boolean
     */
    protected function isArrayObject($value)
    {
        return $value instanceof Collection;
    }

    /**
     * Get an array of Collection values.
     *
	 * @param  object  $value
     * @return array
     */
    protected function getArrayObjectValue($value)
    {
        return $value->all();
    }

}
