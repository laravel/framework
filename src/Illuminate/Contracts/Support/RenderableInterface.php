<?php namespace Illuminate\Contracts\Support;

interface RenderableInterface {

	/**
	 * Get the evaluated contents of the object.
	 *
	 * @return string
	 */
	public function render();

}
