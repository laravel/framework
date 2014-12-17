<?php namespace Illuminate\Session;

trait ExpirationAwareTrait {
	
	/**
	 * The total number of minutes that a session is valid for.
	 *
	 * @var int|null
	 */
	protected $lifetime = null;

	/**
	 * Sets the number of minutes that a session is valid for.
	 *
	 * @param $lifetime  int
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
	 * Gets the number of minutes that a session is valid for.
	 *
	 * @return int|null
	 */
	public function getLifetime()
	{
		return $this->lifetime;
	}

	/**
	 * Runs the garbage collection (gc) method using $this->lifetime.
	 *
	 * @return bool
	 */
	public function garbageCollect()
	{
		return $this->gc($this->lifetime * 60);
	}

}
