<?php

/**
 * A response from the beanstalkd server
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
interface Pheanstalk_Response
{
	// global error reponses
	const RESPONSE_OUT_OF_MEMORY = 'OUT_OF_MEMORY';
	const RESPONSE_INTERNAL_ERROR = 'INTERNAL_ERROR';
	const RESPONSE_DRAINING = 'DRAINING';
	const RESPONSE_BAD_FORMAT = 'BAD_FORMAT';
	const RESPONSE_UNKNOWN_COMMAND = 'UNKNOWN_COMMAND';

	// command responses
	const RESPONSE_INSERTED = 'INSERTED';
	const RESPONSE_BURIED = 'BURIED';
	const RESPONSE_EXPECTED_CRLF = 'EXPECTED_CRLF';
	const RESPONSE_JOB_TOO_BIG = 'JOB_TOO_BIG';
	const RESPONSE_USING = 'USING';
	const RESPONSE_DEADLINE_SOON = 'DEADLINE_SOON';
	const RESPONSE_RESERVED = 'RESERVED';
	const RESPONSE_DELETED = 'DELETED';
	const RESPONSE_NOT_FOUND = 'NOT_FOUND';
	const RESPONSE_RELEASED = 'RELEASED';
	const RESPONSE_WATCHING = 'WATCHING';
	const RESPONSE_NOT_IGNORED = 'NOT_IGNORED';
	const RESPONSE_FOUND = 'FOUND';
	const RESPONSE_KICKED = 'KICKED';
	const RESPONSE_OK = 'OK';
	const RESPONSE_TIMED_OUT = 'TIMED_OUT';
	const RESPONSE_TOUCHED = 'TOUCHED';
	const RESPONSE_PAUSED = 'PAUSED';

	/**
	 * The name of the response
	 * @return string
	 */
	public function getResponseName();
}
