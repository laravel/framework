<?php namespace Illuminate\Support;

use ArrayAccess;
use JsonSerializable;
use Illuminate\Support\Traits\Attributable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class Fluent implements ArrayAccess, Arrayable, Jsonable, JsonSerializable {

	use Attributable;

	/**
	 * Create a new fluent container instance.
	 *
	 * @param  array|object	$attributes
	 * @return void
	 */
	public function __construct($attributes = [])
	{
		foreach ($attributes as $key => $value)
		{
			$this->attributes[$key] = $value;
		}
	}

}
