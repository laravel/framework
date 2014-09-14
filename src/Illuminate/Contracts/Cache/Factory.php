<?php namespace Illuminate\Contracts\Cache;

interface Factory {

	/**
	 * Get a cache driver instance.
	 *
	 * @param  string  $driver
	 * @return mixed
	 */
	public function driver($driver = null);

}
