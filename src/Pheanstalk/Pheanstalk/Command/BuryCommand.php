<?php

/**
 * The 'bury' command.
 * Puts a job into a 'buried' state, revived only by 'kick' command.
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pheanstalk_Command_BuryCommand
	extends Pheanstalk_Command_AbstractCommand
	implements Pheanstalk_ResponseParser
{
	private $_job;
	private $_priority;

	/**
	 * @param object $job Pheanstalk_Job
	 * @param int $priority From 0 (most urgent) to 0xFFFFFFFF (least urgent)
	 */
	public function __construct($job, $priority)
	{
		$this->_job = $job;
		$this->_priority = $priority;
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_Command::getCommandLine()
	 */
	public function getCommandLine()
	{
		return sprintf(
			'bury %d %d',
			$this->_job->getId(),
			$this->_priority
		);
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_ResponseParser::parseRespose()
	 */
	public function parseResponse($responseLine, $responseData)
	{
		if ($responseLine == Pheanstalk_Response::RESPONSE_NOT_FOUND)
		{
			throw new Pheanstalk_Exception_ServerException(sprintf(
				'%s: Job %d is not reserved or does not exist.',
				$responseLine,
				$this->_job->getId()
			));
		}
		elseif ($responseLine == Pheanstalk_Response::RESPONSE_BURIED)
		{
			return $this->_createResponse(Pheanstalk_Response::RESPONSE_BURIED);
		}
		else
		{
			throw new Pheanstalk_Exception('Unhandled response: '.$responseLine);
		}
	}
}
