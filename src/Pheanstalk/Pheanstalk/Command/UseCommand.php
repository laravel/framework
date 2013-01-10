<?php

/**
 * The 'use' command.
 *
 * The "use" command is for producers. Subsequent put commands will put jobs into
 * the tube specified by this command. If no use command has been issued, jobs
 * will be put into the tube named "default".
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pheanstalk_Command_UseCommand
	extends Pheanstalk_Command_AbstractCommand
	implements Pheanstalk_ResponseParser
{
	private $_tube;

	/**
	 * @param string $tube The name of the tube to use
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
		return 'use '.$this->_tube;
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_ResponseParser::parseRespose()
	 */
	public function parseResponse($responseLine, $responseData)
	{
		return $this->_createResponse('USING', array(
			'tube' => preg_replace('#^USING (.+)$#', '$1', $responseLine)
		));
	}
}
