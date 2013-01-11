<?php

/**
 * The 'stats-job' command.
 * Gives statistical information about the specified job if it exists.
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pheanstalk_Command_StatsJobCommand
	extends Pheanstalk_Command_AbstractCommand
{
	private $_jobId;

	/**
	 * @param Pheanstalk_Job or int $job
	 */
	public function __construct($job)
	{
		$this->_jobId = is_object($job) ? $job->getId() : $job;
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_Command::getCommandLine()
	 */
	public function getCommandLine()
	{
		return sprintf('stats-job %d', $this->_jobId);
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_Command::getResponseParser()
	 */
	public function getResponseParser()
	{
		return new Pheanstalk_YamlResponseParser(
			Pheanstalk_YamlResponseParser::MODE_DICT
		);
	}
}
