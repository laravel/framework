<?php namespace Illuminate\Support\Collection;

class Each {

	protected $collection;

	public function __construct($collection)
	{
		$this->collection = $collection;
	}

	public function __call($name, $arguments)
	{
		$output_objects = array();

		foreach($this->collection as $object)
		{
			$output_objects[] = call_user_func_array(array($object, $name), $arguments);
		}

		return new Collection($output_objects);
	}
}
