<?php

/**
 * Common functionality for Pheanstalk_Command implementations.
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
abstract class Pheanstalk_Command_AbstractCommand
	implements Pheanstalk_Command
{
	/* (non-phpdoc)
	 * @see Pheanstalk_Command::hasData()
	 */
	public function hasData()
	{
		return false;
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_Command::getData()
	 */
	public function getData()
	{
		throw new Pheanstalk_Exception_CommandException('Command has no data');
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_Command::getDataLength()
	 */
	public function getDataLength()
	{
		throw new Pheanstalk_Exception_CommandException('Command has no data');
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_Command::getResponseParser()
	 */
	public function getResponseParser()
	{
		// concrete implementation must either:
		// a) implement Pheanstalk_ResponseParser
		// b) override this getResponseParser method
		return $this;
	}

	/**
	 * The string representation of the object.
	 * @return string
	 */
	public function __toString()
	{
		return $this->getCommandLine();
	}

	// ----------------------------------------
	// protected

	/**
	 * Creates a Pheanstalk_Response for the given data
	 * @param array
	 * @return object Pheanstalk_Response
	 */
	protected function _createResponse($name, $data = array())
	{
		return new Pheanstalk_Response_ArrayResponse($name, $data);
	}
}
