<?php namespace Illuminate\Contracts\View;

use Illuminate\Contracts\Support\Renderable;

interface View extends Renderable {

	/**
	 * Add a piece of data to the view.
	 *
	 * @param  string|array  $key
	 * @param  mixed   $value
	 * @return $this
	 */
	public function with($key, $value = null);

}
