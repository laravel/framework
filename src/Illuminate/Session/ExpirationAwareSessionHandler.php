<?php namespace Illuminate\Session;

use SessionHandlerInterface;

abstract class ExpirationAwareSessionHandler implements SessionHandlerInterface, ExpirationAwareInterface {
	
	/**
	 * The total number of minutes that a session is valid for.
	 *
	 * @var int
	 */
	protected $lifetime;

	/**
	 * {@inheritDoc}
	 */
	public function setLifetime($lifetime)
	{
		$this->lifetime = $lifetime;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLifetime()
	{
		return $this->lifetime;
	}

	/**
	 * Runs the garbage collection (gc) method using $lifetime.
	 */
	public function garbageCollect()
	{
		return $this->gc($this->lifetime * 60);
	}

}
