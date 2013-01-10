<?php

/**
 * The 'stats-tube' command.
 * Gives statistical information about the specified tube if it exists.
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pheanstalk_Command_StatsTubeCommand
	extends Pheanstalk_Command_AbstractCommand
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
		return sprintf('stats-tube %s', $this->_tube);
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
