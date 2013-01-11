<?php

/**
 * The 'list-tube-used' command.
 * Returns the tube currently being used by the client.
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pheanstalk_Command_ListTubeUsedCommand
	extends Pheanstalk_Command_AbstractCommand
	implements Pheanstalk_ResponseParser
{
	/* (non-phpdoc)
	 * @see Pheanstalk_Command::getCommandLine()
	 */
	public function getCommandLine()
	{
		return 'list-tube-used';
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
