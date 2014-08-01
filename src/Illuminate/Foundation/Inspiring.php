<?php namespace Illuminate\Foundation;

use Illuminate\Support\Collection;

class Inspiring {

	/**
	 * Get an inspiring quote.
	 *
	 * @return string
	 */
	public static function quote()
	{
		return Collection::make([

			'When there is no desire, all things are at peace. - Laozi',

		])->random();
	}

}