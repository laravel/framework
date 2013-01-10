<?php

/**
 * A Pheanstalk_Socket implementation around a fsockopen() stream.
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pheanstalk_Socket_NativeSocket implements Pheanstalk_Socket
{
	/**
	 * The default timeout for a blocking read on the socket
	 */
	const SOCKET_TIMEOUT = 1;

	/**
	 * Number of retries for attempted writes which return zero length.
	 */
	const WRITE_RETRIES = 8;

	private $_socket;

	/**
	 * @param string $host
	 * @param int $port
	 * @param int $connectTimeout
	 */
	public function __construct($host, $port, $connectTimeout)
	{
		$this->_socket = $this->_wrapper()
			->fsockopen($host, $port, $errno, $errstr, $connectTimeout);

		if (!$this->_socket)
		{
			throw new Pheanstalk_Exception_ConnectionException($errno, $errstr . " (connecting to $host:$port)");
		}

		$this->_wrapper()
			->stream_set_timeout($this->_socket, self::SOCKET_TIMEOUT);
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_Socket::write()
	 */
	public function write($data)
	{
		$history = new Pheanstalk_Socket_WriteHistory(self::WRITE_RETRIES);

		for ($written = 0, $fwrite = 0; $written < strlen($data); $written += $fwrite)
		{
			$fwrite = $this->_wrapper()
				->fwrite($this->_socket, substr($data, $written));

			$history->log($fwrite);

			if ($history->isFullWithNoWrites())
			{
				throw new Pheanstalk_Exception_SocketException(sprintf(
					'fwrite() failed to write data after %d tries',
					self::WRITE_RETRIES
				));
			}
		}
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_Socket::write()
	 */
	public function read($length)
	{
		$read = 0;
		$parts = array();

		while ($read < $length && !$this->_wrapper()->feof($this->_socket))
		{
			$data = $this->_wrapper()
				->fread($this->_socket, $length - $read);

			if ($data === false)
			{
				throw new Pheanstalk_Exception_SocketException('fread() returned false');
			}

			$read += strlen($data);
			$parts []= $data;
		}

		return implode($parts);
	}

	/* (non-phpdoc)
	 * @see Pheanstalk_Socket::write()
	 */
	public function getLine($length = null)
	{
		do
		{
			$data = isset($length) ?
				$this->_wrapper()->fgets($this->_socket, $length) :
				$this->_wrapper()->fgets($this->_socket);

			if ($this->_wrapper()->feof($this->_socket))
			{
				throw new Pheanstalk_Exception_SocketException("Socket closed by server!");
			}
		}
		while ($data === false);

		return rtrim($data);
	}

	// ----------------------------------------

	/**
	 * Wrapper class for all stream functions.
	 * Facilitates mocking/stubbing stream operations in unit tests.
	 */
	private function _wrapper()
	{
		return Pheanstalk_Socket_StreamFunctions::instance();
	}
}
