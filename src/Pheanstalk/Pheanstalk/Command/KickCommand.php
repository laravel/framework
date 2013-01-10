<?php

/**
 * The 'kick' command.
 * Kicks buried or delayed jobs into a 'ready' state.
 * If there are buried jobs, it will kick up to $max of them.
 * Otherwise, it will kick up to $max delayed jobs.
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pheanstalk_Command_KickCommand
	extends Pheanstalk_Command_AbstractCommand
	implements Pheanstalk_ResponseParser
{
	private $_max;

	/**
	 * @param int $max The maximum number of jobs to kick
	 */
	public function __construct($max)
	{
		$this->_max = (int)$max;
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_Command::getCommandLine()
	 */
	public function getCommandLine()
	{
		return 'kick '.$this->_max;
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_ResponseParser::parseRespose()
	 */
	public function parseResponse($responseLine, $responseData)
	{
		list($code, $count) = explode(' ', $responseLine);

		return $this->_createResponse($code, array(
			'kicked' => (int)$count,
		));
	}
}
