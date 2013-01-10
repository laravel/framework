<?php

/**
 * A job in a beanstalkd server
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pheanstalk_Job
{
	const STATUS_READY = 'ready';
	const STATUS_RESERVED = 'reserved';
	const STATUS_DELAYED = 'delayed';
	const STATUS_BURIED = 'buried';

	private $_id;
	private $_data;

	/**
	 * @param int $id The job ID
	 * @param string $data The job data
	 */
	public function __construct($id, $data)
	{
		$this->_id = (int)$id;
		$this->_data = $data;
	}

	/**
	 * The job ID, unique on the beanstalkd server.
	 * @return int
	 */
	public function getId()
	{
		return $this->_id;
	}

	/**
	 * The job data.
	 * @return string
	 */
	public function getData()
	{
		return $this->_data;
	}
}
