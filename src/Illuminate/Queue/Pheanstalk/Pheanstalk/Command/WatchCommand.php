<?php

/**
 * The 'watch' command.
 * Adds a tube to the watchlist to reserve jobs from.
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pheanstalk_Command_WatchCommand
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
		return 'watch '.$this->_tube;
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_ResponseParser::parseRespose()
	 */
	public function parseResponse($responseLine, $responseData)
	{
		return $this->_createResponse('WATCHING', array(
			'count' => preg_replace('#^WATCHING (.+)$#', '$1', $responseLine)
		));
	}
}
