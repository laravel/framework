<?php

/**
 * A connection to a beanstalkd server
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pheanstalk_Connection
{
	const CRLF = "\r\n";
	const CRLF_LENGTH = 2;
	const DEFAULT_CONNECT_TIMEOUT = 2;

	// responses which are global errors, mapped to their exception short-names
	private static $_errorResponses = array(
		Pheanstalk_Response::RESPONSE_OUT_OF_MEMORY => 'OutOfMemory',
		Pheanstalk_Response::RESPONSE_INTERNAL_ERROR => 'InternalError',
		Pheanstalk_Response::RESPONSE_DRAINING => 'Draining',
		Pheanstalk_Response::RESPONSE_BAD_FORMAT => 'BadFormat',
		Pheanstalk_Response::RESPONSE_UNKNOWN_COMMAND => 'UnknownCommand',
	);

	// responses which are followed by data
	private static $_dataResponses = array(
		Pheanstalk_Response::RESPONSE_RESERVED,
		Pheanstalk_Response::RESPONSE_FOUND,
		Pheanstalk_Response::RESPONSE_OK,
	);

	private $_socket;
	private $_hostname;
	private $_port;
	private $_connectTimeout;

	/**
	 * @param string $hostname
	 * @param int $port
	 * @param float $connectTimeout
	 */
	public function __construct($hostname, $port, $connectTimeout = null)
	{
		if (is_null($connectTimeout) || !is_numeric($connectTimeout))
			$connectTimeout = self::DEFAULT_CONNECT_TIMEOUT;

		$this->_hostname = $hostname;
		$this->_port = $port;
		$this->_connectTimeout = $connectTimeout;
	}

	/**
	 * Sets a manually created socket, used for unit testing.
	 *
	 * @param Pheanstalk_Socket $socket
	 * @chainable
	 */
	public function setSocket(Pheanstalk_Socket $socket)
	{
		$this->_socket = $socket;
		return $this;
	}

	/**
	 * @param object $command Pheanstalk_Command
	 * @return object Pheanstalk_Response
	 * @throws Pheanstalk_Exception_ClientException
	 */
	public function dispatchCommand($command)
	{
		$socket = $this->_getSocket();

		$to_send = $command->getCommandLine().self::CRLF;

		if ($command->hasData())
		{
			$to_send .= $command->getData().self::CRLF;
		}

		$socket->write($to_send);

		$responseLine = $socket->getLine();
		$responseName = preg_replace('#^(\S+).*$#s', '$1', $responseLine);

		if (isset(self::$_errorResponses[$responseName]))
		{
			$exception = sprintf(
				'Pheanstalk_Exception_Server%sException',
				self::$_errorResponses[$responseName]
			);

			throw new $exception(sprintf(
				"%s in response to '%s'",
				$responseName,
				$command
			));
		}

		if (in_array($responseName, self::$_dataResponses))
		{
			$dataLength = preg_replace('#^.*\b(\d+)$#', '$1', $responseLine);
			$data = $socket->read($dataLength);

			$crlf = $socket->read(self::CRLF_LENGTH);
			if ($crlf !== self::CRLF)
			{
				throw new Pheanstalk_Exception_ClientException(sprintf(
					'Expected %d bytes of CRLF after %d bytes of data',
					self::CRLF_LENGTH,
					$dataLength
				));
			}
		}
		else
		{
			$data = null;
		}

		return $command
			->getResponseParser()
			->parseResponse($responseLine, $data);
	}

	/**
	 * Returns the connect timeout for this connection.
	 *
	 * @return float
	 */
	public function getConnectTimeout()
	{
		return $this->_connectTimeout;
	}

	/**
	 * Returns the host for this connection.
	 *
	 * @return string
	 */
	public function getHost()
	{
		return $this->_hostname;
	}

	/**
	 * Returns the port for this connection.
	 *
	 * @return int
	 */
	public function getPort()
	{
		return $this->_port;
	}

	// ----------------------------------------

	/**
	 * Socket handle for the connection to beanstalkd
	 *
	 * @return Pheanstalk_Socket
	 * @throws Pheanstalk_Exception_ConnectionException
	 */
	private function _getSocket()
	{
		if (!isset($this->_socket))
		{
			$this->_socket = new Pheanstalk_Socket_NativeSocket(
				$this->_hostname,
				$this->_port,
				$this->_connectTimeout
			);
		}

		return $this->_socket;
	}
    
    /**
     * Checks connection to the beanstalkd socket
     *
     * @return true|false
     */
    public function isServiceListening()
    {
        try
        {
            $this->_getSocket();            
        } 
        catch (Pheanstalk_Exception_ConnectionException $e)
        {
            return false;
        }
        return true;
    }
}
