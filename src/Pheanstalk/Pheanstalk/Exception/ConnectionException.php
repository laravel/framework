<?php

/**
 * An exception relating to the client connection to the beanstalkd server
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pheanstalk_Exception_ConnectionException
	extends Pheanstalk_Exception_ClientException
{
	/**
	 * @param int $errno The connection error code
	 * @param string $errstr The connection error message
	 */
	public function __construct($errno, $errstr)
	{
		parent::__construct(sprintf('Socket error %d: %s', $errno, $errstr));
	}
}
