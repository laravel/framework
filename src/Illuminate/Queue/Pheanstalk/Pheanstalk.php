<?php

/**
 * Pheanstalk is a pure PHP 5.2+ client for the beanstalkd workqueue.
 * The Pheanstalk class is a simple facade for the various underlying components.
 *
 * @see http://github.com/kr/beanstalkd
 * @see http://xph.us/software/beanstalkd/
 *
 * @author Paul Annesley
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pheanstalk
{
	const DEFAULT_PORT = 11300;
	const DEFAULT_DELAY = 0; // no delay
	const DEFAULT_PRIORITY = 1024; // most urgent: 0, least urgent: 4294967295
	const DEFAULT_TTR = 60; // 1 minute
	const DEFAULT_TUBE = 'default';

	private $_connection;
	private $_using = self::DEFAULT_TUBE;
	private $_watching = array(self::DEFAULT_TUBE => true);

	/**
	 * @param string $host
	 * @param int $port
	 * @param int $connectTimeout
	 */
	public function __construct($host, $port = self::DEFAULT_PORT, $connectTimeout = null)
	{
		$this->setConnection(new Pheanstalk_Connection($host, $port, $connectTimeout));
	}

	/**
	 * @param Pheanstalk_Connection
	 * @chainable
	 */
	public function setConnection($connection)
	{
		$this->_connection = $connection;
		return $this;
	}
    
    /**
     * Returns the connection object
     *
     * @return Pheanstalk_Connection
     */
    public function getConnection()
    {
        return $this->_connection;
    }

	// ----------------------------------------

	/**
	 * Puts a job into a 'buried' state, revived only by 'kick' command.
	 *
	 * @param Pheanstalk_Job $job
	 * @return void
	 */
	public function bury($job, $priority = self::DEFAULT_PRIORITY)
	{
		$this->_dispatch(new Pheanstalk_Command_BuryCommand($job, $priority));
	}

	/**
	 * Permanently deletes a job.
	 *
	 * @param object $job Pheanstalk_Job
	 * @chainable
	 */
	public function delete($job)
	{
		$this->_dispatch(new Pheanstalk_Command_DeleteCommand($job));
		return $this;
	}

	/**
	 * Remove the specified tube from the watchlist.
	 *
	 * Does not execute an IGNORE command if the specified tube is not in the
	 * cached watchlist.
	 *
	 * @param string $tube
	 * @chainable
	 */
	public function ignore($tube)
	{
		if (isset($this->_watching[$tube]))
		{
			$this->_dispatch(new Pheanstalk_Command_IgnoreCommand($tube));
			unset($this->_watching[$tube]);
		}
		return $this;
	}

	/**
	 * Kicks buried or delayed jobs into a 'ready' state.
	 * If there are buried jobs, it will kick up to $max of them.
	 * Otherwise, it will kick up to $max delayed jobs.
	 *
	 * @param int $max The maximum jobs to kick
	 * @return int Number of jobs kicked
	 */
	public function kick($max)
	{
		$response = $this->_dispatch(new Pheanstalk_Command_KickCommand($max));
		return $response['kicked'];
	}

	/**
	 * The names of all tubes on the server.
	 *
	 * @return array
	 */
	public function listTubes()
	{
		return (array) $this->_dispatch(
			new Pheanstalk_Command_ListTubesCommand()
		);
	}

	/**
	 * The names of the tubes being watched, to reserve jobs from.
	 *
	 * Returns the cached watchlist if $askServer is false (the default),
	 * or queries the server for the watchlist if $askServer is true.
	 *
	 * @param bool $askServer
	 * @return array
	 */
	public function listTubesWatched($askServer = false)
	{
		if ($askServer)
		{
			$response = (array) $this->_dispatch(
				new Pheanstalk_Command_ListTubesWatchedCommand()
			);
			$this->_watching = array_fill_keys($response, true);
		}

		return array_keys($this->_watching);
	}

	/**
	 * The name of the current tube used for publishing jobs to.
	 *
	 * Returns the cached value if $askServer is false (the default),
	 * or queries the server for the currently used tube if $askServer
	 * is true.
	 *
	 * @param bool $askServer
	 * @return string
	 */
	public function listTubeUsed($askServer = false)
	{
		if ($askServer)
		{
			$response = $this->_dispatch(
				new Pheanstalk_Command_ListTubeUsedCommand()
			);
			$this->_using = $response['tube'];
		}

		return $this->_using;
	}

	/**
	 * Temporarily prevent jobs being reserved from the given tube.
	 *
	 * @param string $tube The tube to pause
	 * @param int $delay Seconds before jobs may be reserved from this queue.
	 * @chainable
	 */
	public function pauseTube($tube, $delay)
	{
		$this->_dispatch(new Pheanstalk_Command_PauseTubeCommand($tube, $delay));
		return $this;
	}

	/**
	 * Inspect a job in the system, regardless of what tube it is in.
	 *
	 * @param int $jobId
	 * @return object Pheanstalk_Job
	 */
	public function peek($jobId)
	{
		$response = $this->_dispatch(
			new Pheanstalk_Command_PeekCommand($jobId)
		);

		return new Pheanstalk_Job($response['id'], $response['jobdata']);
	}

	/**
	 * Inspect the next ready job in the specified tube. If no tube is
	 * specified, the currently used tube in used.
	 *
	 * @param string $tube
	 * @return object Pheanstalk_Job
	 */
	public function peekReady($tube = null)
	{
		if ($tube !== null)
		{
			$this->useTube($tube);
		}

		$response = $this->_dispatch(
			new Pheanstalk_Command_PeekCommand(Pheanstalk_Command_PeekCommand::TYPE_READY)
		);

		return new Pheanstalk_Job($response['id'], $response['jobdata']);
	}

	/**
	 * Inspect the shortest-remaining-delayed job in the specified tube. If no
	 * tube is specified, the currently used tube in used.
	 *
	 * @param string $tube
	 * @return object Pheanstalk_Job
	 */
	public function peekDelayed($tube = null)
	{
		if ($tube !== null)
		{
			$this->useTube($tube);
		}

		$response = $this->_dispatch(
			new Pheanstalk_Command_PeekCommand(Pheanstalk_Command_PeekCommand::TYPE_DELAYED)
		);

		return new Pheanstalk_Job($response['id'], $response['jobdata']);
	}

	/**
	 * Inspect the next job in the list of buried jobs of the specified tube.
	 * If no tube is specified, the currently used tube in used.
	 *
	 * @param string $tube
	 * @return object Pheanstalk_Job
	 */
	public function peekBuried($tube = null)
	{
		if ($tube !== null)
		{
			$this->useTube($tube);
		}

		$response = $this->_dispatch(
			new Pheanstalk_Command_PeekCommand(Pheanstalk_Command_PeekCommand::TYPE_BURIED)
		);

		return new Pheanstalk_Job($response['id'], $response['jobdata']);
	}

	/**
	 * Puts a job on the queue.
	 *
	 * @param string $data The job data
	 * @param int $priority From 0 (most urgent) to 0xFFFFFFFF (least urgent)
	 * @param int $delay Seconds to wait before job becomes ready
	 * @param int $ttr Time To Run: seconds a job can be reserved for
	 * @return int The new job ID
	 */
	public function put(
		$data,
		$priority = self::DEFAULT_PRIORITY,
		$delay = self::DEFAULT_DELAY,
		$ttr = self::DEFAULT_TTR
	)
	{
		$response = $this->_dispatch(
			new Pheanstalk_Command_PutCommand($data, $priority, $delay, $ttr)
		);

		return $response['id'];
	}

	/**
	 * Puts a job on the queue using specified tube.
	 *
	 * Using this method is equivalent to calling useTube() then put(), with
	 * the added benefit that it will not execute the USE command if the client
	 * is already using the specified tube.
	 *
	 * @param string $tube The tube to use
	 * @param string $data The job data
	 * @param int $priority From 0 (most urgent) to 0xFFFFFFFF (least urgent)
	 * @param int $delay Seconds to wait before job becomes ready
	 * @param int $ttr Time To Run: seconds a job can be reserved for
	 * @return int The new job ID
	 */
	public function putInTube(
		$tube,
		$data,
		$priority = self::DEFAULT_PRIORITY,
		$delay = self::DEFAULT_DELAY,
		$ttr = self::DEFAULT_TTR
	)
	{
		$this->useTube($tube);

		return $this->put($data, $priority, $delay, $ttr);
	}

	/**
	 * Puts a reserved job back into the ready queue.
	 *
	 * Marks the jobs state as "ready" to be run by any client.
	 * It is normally used when the job fails because of a transitory error.
	 *
	 * @param object $job Pheanstalk_Job
	 * @param int $priority From 0 (most urgent) to 0xFFFFFFFF (least urgent)
	 * @param int $delay Seconds to wait before job becomes ready
	 * @chainable
	 */
	public function release(
		$job,
		$priority = self::DEFAULT_PRIORITY,
		$delay = self::DEFAULT_DELAY
	)
	{
		$this->_dispatch(
			new Pheanstalk_Command_ReleaseCommand($job, $priority, $delay)
		);

		return $this;
	}

	/**
	 * Reserves/locks a ready job in a watched tube.
	 *
	 * A non-null timeout uses the 'reserve-with-timeout' instead of 'reserve'.
	 *
	 * A timeout value of 0 will cause the server to immediately return either a
	 * response or TIMED_OUT.  A positive value of timeout will limit the amount of
	 * time the client will block on the reserve request until a job becomes
	 * available.
	 *
	 * @param int $timeout
	 * @return object Pheanstalk_Job
	 */
	public function reserve($timeout = null)
	{
		$response = $this->_dispatch(
			new Pheanstalk_Command_ReserveCommand($timeout)
		);

		$falseResponses = array(
			Pheanstalk_Response::RESPONSE_DEADLINE_SOON,
			Pheanstalk_Response::RESPONSE_TIMED_OUT,
		);

		if (in_array($response->getResponseName(), $falseResponses))
		{
			return false;
		}
		else
		{
			return new Pheanstalk_Job($response['id'], $response['jobdata']);
		}
	}

	/**
	 * Reserves/locks a ready job from the specified tube.
	 *
	 * A non-null timeout uses the 'reserve-with-timeout' instead of 'reserve'.
	 *
	 * A timeout value of 0 will cause the server to immediately return either a
	 * response or TIMED_OUT.  A positive value of timeout will limit the amount of
	 * time the client will block on the reserve request until a job becomes
	 * available.
	 *
	 * Using this method is equivalent to calling watch(), ignore() then 
	 * reserve(), with the added benefit that it will not execute uneccessary
	 * WATCH or IGNORE commands if the client is already watching the
	 * specified tube.
	 *
	 * @param string $tube
	 * @param int $timeout
	 * @return object Pheanstalk_Job
	 */
	public function reserveFromTube($tube, $timeout = null)
	{
		$this->watchOnly($tube);
		return $this->reserve($timeout);
	}

	/**
	 * Gives statistical information about the specified job if it exists.
	 *
	 * @param Pheanstalk_Job or int $job
	 * @return object
	 */
	public function statsJob($job)
	{
		return $this->_dispatch(new Pheanstalk_Command_StatsJobCommand($job));
	}

	/**
	 * Gives statistical information about the specified tube if it exists.
	 *
	 * @param string $tube
	 * @return object
	 */
	public function statsTube($tube)
	{
		return $this->_dispatch(new Pheanstalk_Command_StatsTubeCommand($tube));
	}

	/**
	 * Gives statistical information about the beanstalkd system as a whole.
	 *
	 * @return object
	 */
	public function stats()
	{
		return $this->_dispatch(new Pheanstalk_Command_StatsCommand());
	}

	/**
	 * Allows a worker to request more time to work on a job.
	 *
	 * This is useful for jobs that potentially take a long time, but you still want
	 * the benefits of a TTR pulling a job away from an unresponsive worker.  A worker
	 * may periodically tell the server that it's still alive and processing a job
	 * (e.g. it may do this on DEADLINE_SOON).
	 *
	 * @param Pheanstalk_Job $job
	 * @chainable
	 */
	public function touch($job)
	{
		$this->_dispatch(new Pheanstalk_Command_TouchCommand($job));
		return $this;
	}

	/**
	 * Change to the specified tube name for publishing jobs to.
	 * This method would be called 'use' if it were not a PHP reserved word.
	 *
	 * Does not execute a USE command if the client is already using the
	 * specified tube.
	 *
	 * @param string $tube
	 * @chainable
	 */
	public function useTube($tube)
	{
		if ($this->_using != $tube)
		{
			$this->_dispatch(new Pheanstalk_Command_UseCommand($tube));
			$this->_using = $tube;
		}
		return $this;
	}

	/**
	 * Add the specified tube to the watchlist, to reserve jobs from.
	 *
	 * Does not execute a WATCH command if the client is already watching the
	 * specified tube.
	 *
	 * @param string $tube
	 * @chainable
	 */
	public function watch($tube)
	{
		if (!isset($this->_watching[$tube]))
		{
			$this->_dispatch(new Pheanstalk_Command_WatchCommand($tube));
			$this->_watching[$tube] = true;
		}
		return $this;
	}

	/**
	 * Adds the specified tube to the watchlist, to reserve jobs from, and
	 * ignores any other tubes remaining on the watchlist.
	 *
	 * @param string $tube
	 * @chainable
	 */
	public function watchOnly($tube)
	{
		$this->watch($tube);
		
		$ignoreTubes = array_diff_key($this->_watching, array($tube => true));
		foreach ($ignoreTubes as $ignoreTube => $true)
		{
			$this->ignore($ignoreTube);
		}
		
		return $this;
	}

	// ----------------------------------------

	/**
	 * Dispatches the specified command to the connection object.
	 *
	 * If a SocketException occurs, the connection is reset, and the command is
	 * re-attempted once.
	 *
	 * @param Pheanstalk_Command $command
	 * @return Pheanstalk_Response
	 */
	private function _dispatch($command)
	{
		try
		{
			$response = $this->_connection->dispatchCommand($command);
		}
		catch (Pheanstalk_Exception_SocketException $e)
		{
			$this->_reconnect();
			$response = $this->_connection->dispatchCommand($command);
		}

		return $response;
	}

	/**
	 * Creates a new connection object, based on the existing connection object,
	 * and re-establishes the used tube and watchlist.
	 */
	private function _reconnect()
	{
		$new_connection = new Pheanstalk_Connection(
			$this->_connection->getHost(),
			$this->_connection->getPort(),
			$this->_connection->getConnectTimeout()
		);

		$this->setConnection($new_connection);

		if ($this->_using != self::DEFAULT_TUBE)
		{
			$tube = $this->_using;
			$this->_using = null;
			$this->useTube($tube);
		}

		foreach ($this->_watching as $tube => $true)
		{
			if ($tube != self::DEFAULT_TUBE)
			{
				unset($this->_watching[$tube]);
				$this->watch($tube);
			}
		}

		if (!isset($this->_watching[self::DEFAULT_TUBE]))
		{
			$this->ignore(self::DEFAULT_TUBE);
		}
	}
}
