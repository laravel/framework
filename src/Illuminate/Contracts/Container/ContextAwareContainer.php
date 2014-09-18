<?php namespace Illuminate\Contracts\Container;

interface ContextAwareContainer extends Container {

	/**
	 * Define a contextual binding.
	 *
	 * @param  string  $concrete
	 * @return \Illuminate\Contracts\Container\ContextualBindingBuilder
	 */
	public function when($concrete);

}
