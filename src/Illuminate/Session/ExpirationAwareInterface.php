<?php namespace Illuminate\Session;

interface ExpirationAwareInterface {

	/**
	 * Get the expiration time of session data in minutes
	 *
	 * @return int
	 */
	public function getLifetime();

	/**
	 * Set the expiration time of session data in minutes
	 *
	 * @param  int   $lifetime
	 * @return int
	 */
	public function setLifetime($lifetime);

	/**
	 * Runs the garbage collector using the minutes set in the lifetime
	 *
	 * @return bool
	 */
	public function garbageCollect();
}
