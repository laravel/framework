<?php namespace Illuminate\Session;

trait ExpirationAwareTrait {
	
	/**
	 * The total number of minutes that a session is valid for.
	 *
	 * @var int
	 */
	protected $lifetime = null;

	/**
	 * {@inheritDoc}
	 */
	public function setLifetime($lifetime)
	{
		if (!is_numeric($lifetime))
		{
			throw new \InvalidArgumentException('setLifetime expects a number');
		}

		if ($lifetime < 0)
		{
			throw new \InvalidArgumentException('setLifetime expects a number');
		}

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
