<?php

/**
 * A limited history of recent socket write length/success.
 * Facilitates retrying zero-length writes a limited number of times,
 * avoiding infinite loops.
 *
 * Based on a patch from https://github.com/leprechaun
 * https://github.com/pda/pheanstalk/pull/24
 *
 * A bitfield could be used instead of an array for efficiency.
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pheanstalk_Socket_WriteHistory
{
	private $_limit;
	private $_data = array();

	/**
	 * @param int $limit
	 */
	public function __construct($limit)
	{
		$this->_limit = $limit;
	}

	/**
	 * Whether the history has reached its limit of entries.
	 */
	public function isFull()
	{
		return count($this->_data) >= $this->_limit;
	}

	public function hasWrites()
	{
		return (bool)array_sum($this->_data);
	}

	public function isFullWithNoWrites()
	{
		return $this->isFull() && !$this->hasWrites();
	}

	/**
	 * Logs the return value from a write call.
	 * Returns the input value.
	 */
	public function log($write)
	{
		if ($this->isFull())
			array_shift($this->_data);

		$this->_data []= (int)$write;

		return $write;
	}
}
