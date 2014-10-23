<?php namespace Illuminate\Events\Annotations\Annotations;

/**
 * @Annotation
 */
class Hears {

	/**
	 * The events the annotation hears.
	 *
	 * @var array
	 */
	public $events;

	/**
	 * Create a new annotation instance.
	 *
	 * @return void
	 */
	public function __construct(array $values = array())
	{
		$this->events = (array) $values['value'];
	}

}
