<?php

/**
 * The 'ignore' command.
 * Removes a tube from the watch list to reserve jobs from.
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pheanstalk_Command_IgnoreCommand
	extends Pheanstalk_Command_AbstractCommand
	implements Pheanstalk_ResponseParser
{
	private $_tube;

	/**
	 * @param string $tube
	 */
	public function __construct($tube)
	{
		$this->_tube = $tube;
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_Command::getCommandLine()
	 */
	public function getCommandLine()
	{
		return 'ignore '.$this->_tube;
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_ResponseParser::parseRespose()
	 */
	public function parseResponse($responseLine, $responseData)
	{
		if (preg_match('#^WATCHING (\d+)$#', $responseLine, $matches))
		{
			return $this->_createResponse('WATCHING', array(
				'count' => (int)$matches[1]
			));
		}
		elseif ($responseLine == Pheanstalk_Response::RESPONSE_NOT_IGNORED)
		{
			throw new Pheanstalk_Exception_ServerException($responseLine .
				': cannot ignore last tube in watchlist');
		}
		else
		{
			throw new Pheanstalk_Exception('Unhandled response: '.$responseLine);
		}
	}
}
