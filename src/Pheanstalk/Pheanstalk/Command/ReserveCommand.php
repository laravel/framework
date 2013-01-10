<?php

/**
 * The 'reserve' command.
 * Reserves/locks a ready job in a watched tube.
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pheanstalk_Command_ReserveCommand
	extends Pheanstalk_Command_AbstractCommand
	implements Pheanstalk_ResponseParser
{
	private $_timeout;

	/**
	 * A timeout value of 0 will cause the server to immediately return either a
	 * response or TIMED_OUT.  A positive value of timeout will limit the amount of
	 * time the client will block on the reserve request until a job becomes
	 * available.
	 *
	 * @param int $timeout
	 */
	public function __construct($timeout = null)
	{
		$this->_timeout = $timeout;
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_Command::getCommandLine()
	 */
	public function getCommandLine()
	{
		return isset($this->_timeout) ?
			sprintf('reserve-with-timeout %s', $this->_timeout) :
			'reserve';
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_ResponseParser::parseRespose()
	 */
	public function parseResponse($responseLine, $responseData)
	{
		if ($responseLine === Pheanstalk_Response::RESPONSE_DEADLINE_SOON ||
			$responseLine === Pheanstalk_Response::RESPONSE_TIMED_OUT)
		{
			return $this->_createResponse($responseLine);
		}

		list($code, $id) = explode(' ', $responseLine);

		return $this->_createResponse($code, array(
			'id' => (int)$id,
			'jobdata' => $responseData,
		));
	}
}
