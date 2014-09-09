<?php namespace Illuminate\Contracts\Validation;

use Illuminate\Contracts\Container\Container;

interface ValidatesWhenResolved {

	/**
	 * Validate the given class instance.
	 *
	 * @return void
	 */
	public function validate();

	/**
	 * Set the container implementation.
	 *
	 * @param  Container  $container
	 * @return $this
	 */
	public function setContainer(Container $container);

}
