<?php

/**
 * The 'release' command.
 * Releases a reserved job back onto the ready queue.
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pheanstalk_Command_ReleaseCommand
	extends Pheanstalk_Command_AbstractCommand
	implements Pheanstalk_ResponseParser
{
	private $_job;
	private $_priority;
	private $_delay;

	/**
	 * @param object $job Pheanstalk_Job
	 * @param int $priority From 0 (most urgent) to 0xFFFFFFFF (least urgent)
	 * @param int $delay Seconds to wait before job becomes ready
	 */
	public function __construct($job, $priority, $delay)
	{
		$this->_job = $job;
		$this->_priority = $priority;
		$this->_delay = $delay;
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_Command::getCommandLine()
	 */
	public function getCommandLine()
	{
		return sprintf(
			'release %d %d %d',
			$this->_job->getId(),
			$this->_priority,
			$this->_delay
		);
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_ResponseParser::parseRespose()
	 */
	public function parseResponse($responseLine, $responseData)
	{
		if ($responseLine == Pheanstalk_Response::RESPONSE_BURIED)
		{
			throw new Pheanstalk_Exception_ServerException(sprintf(
				'Job %s %d: out of memory trying to grow data structure',
				$this->_job->getId(),
				$responseLine
			));
		}

		if ($responseLine == Pheanstalk_Response::RESPONSE_NOT_FOUND)
		{
			throw new Pheanstalk_Exception_ServerException(sprintf(
				'Job %d %s: does not exist or is not reserved by client',
				$this->_job->getId(),
				$responseLine
			));
		}

		return $this->_createResponse($responseLine);
	}
}
