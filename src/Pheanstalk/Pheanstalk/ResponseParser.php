<?php

/**
 * A parser for response data sent from the beanstalkd server
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
interface Pheanstalk_ResponseParser
{
	/**
	 * Parses raw response data into a Pheanstalk_Response object
	 * @param string $responseLine Without trailing CRLF
	 * @param string $responseData (null if no data)
	 * @return object Pheanstalk_Response
	 */
	public function parseResponse($responseLine, $responseData);
}
