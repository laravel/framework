<?php namespace Illuminate\Contracts\Auth\Social;

interface Factory {

	/**
	 * Get an OAuth provider implementation.
	 *
	 * @param  string  $driver
	 * @return \Illuminate\Contracts\Auth\Social\Provider
	 */
	public function driver($driver = null);

}
