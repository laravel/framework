<?php

/**
 * The 'list-tubes-watched' command.
 * Lists the tubes on the watchlist.
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pheanstalk_Command_ListTubesWatchedCommand
	extends Pheanstalk_Command_AbstractCommand
{
	/* (non-phpdoc)
	 * @see Pheanstalk_Command::getCommandLine()
	 */
	public function getCommandLine()
	{
		return 'list-tubes-watched';
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_Command::getResponseParser()
	 */
	public function getResponseParser()
	{
		return new Pheanstalk_YamlResponseParser(
			Pheanstalk_YamlResponseParser::MODE_LIST
		);
	}
}
