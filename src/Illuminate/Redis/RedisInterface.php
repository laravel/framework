<?php namespace Illuminate\Redis;

interface RedisInterface {

	/**
	 * Get a specific Redis connection instance.
	 *
	 * @param  string  $name
	 * @return mixed
	 */
	public function connection($name = null);

}
