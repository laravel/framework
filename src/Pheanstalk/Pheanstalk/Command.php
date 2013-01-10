<?php

/**
 * A command to be sent to the beanstalkd server, and response processing logic
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
interface Pheanstalk_Command
{
	const COMMAND_PUT = 'put';
	const COMMAND_USE = 'use';
	const COMMAND_RESERVE = 'reserve';
	const COMMAND_DELETE = 'delete';
	const COMMAND_RELEASE = 'release';
	const COMMAND_BURY = 'bury';
	const COMMAND_WATCH = 'watch';
	const COMMAND_IGNORE = 'ignore';
	const COMMAND_PEEK = 'peek';
	const COMMAND_KICK = 'kick';
	const COMMAND_STATS_JOB = 'stats-job';
	const COMMAND_STATS = 'stats';
	const COMMAND_LIST_TUBES = 'list-tubes';
	const COMMAND_LIST_TUBE_USED = 'list-tube-used';
	const COMMAND_LIST_TUBES_WATCHED = 'list-tubes-watched';

	/**
	 * The command line, without trailing CRLF
	 * @return string
	 */
	public function getCommandLine();

	/**
	 * Whether the command is followed by data
	 * @return boolean
	 */
	public function hasData();

	/**
	 * The binary data to follow the command
	 * @return string
	 * @throws Pheanstalk_Exception_CommandException If command has no data
	 */
	public function getData();

	/**
	 * The length of the binary data in bytes
	 * @return int
	 * @throws Pheanstalk_Exception_CommandException If command has no data
	 */
	public function getDataLength();

	/**
	 * The response parser for the command.
	 * @return Pheanstalk_ResponseParser
	 */
	public function getResponseParser();
}
