<?php
class Eloquent
{
	protected $attributes = array();

	public function __construct($attributes)
	{
		$this->attributes = $attributes;
	}

	public function __get($attribute)
	{
		$accessor = 'get' .lcfirst($attribute). 'Attribute';
		if (method_exists($this, $accessor)) {
			return $this->$accessor();
		}

		return $this->$attribute;
	}

	public function getSomeAttribute()
	{
		return $this->attributes['some'];
	}
}