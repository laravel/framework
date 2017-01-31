<?php namespace Illuminate\Session;

use Illuminate\Database\Connection;

class DatabaseSessionHandler extends ExpirationAwareSessionHandler implements ExistenceAwareInterface {

	/**
	 * The database connection instance.
	 *
	 * @var \Illuminate\Database\Connection
	 */
	protected $connection;

	/**
	 * The name of the session table.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The existence state of the session.
	 *
	 * @var bool
	 */
	protected $exists;

	/**
	 * Create a new database session handler instance.
	 *
	 * @param  \Illuminate\Database\Connection  $connection
	 * @param  string  $table
	 * @return void
	 */
	public function __construct(Connection $connection, $table)
	{
		$this->table = $table;
		$this->connection = $connection;
	}

	/**
	 * {@inheritDoc}
	 */
	public function open($savePath, $sessionName)
	{
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function close()
	{
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function read($sessionId)
	{
		// Unless we identify otherwise, this is a new session
		$this->exists = false;

		// Get the session from the database
		$session = $this->getQuery()->find($sessionId);

		// If the session is null, no data
		if (is_null($session)) return;

		// Delete the session if it's still in the database but it has expired
		if ($session->last_activity <= time() - ($this->lifetime * 60))
		{
			$this->destroy($sessionId);
			return;
		}

		// This is a new session
		$this->exists = true;

		return base64_decode($session->payload);
	}

	/**
	 * {@inheritDoc}
	 */
	public function write($sessionId, $data)
	{
		if ($this->exists)
		{
			$this->getQuery()->where('id', $sessionId)->update([
				'payload' => base64_encode($data), 'last_activity' => time(),
			]);
		}
		else
		{
			$this->getQuery()->insert([
				'id' => $sessionId, 'payload' => base64_encode($data), 'last_activity' => time(),
			]);
		}

		$this->exists = true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function destroy($sessionId)
	{
		$this->getQuery()->where('id', $sessionId)->delete();
	}

	/**
	 * {@inheritDoc}
	 */
	public function gc($lifetime)
	{
		$this->getQuery()->where('last_activity', '<=', time() - $lifetime)->delete();
	}

	/**
	 * Get a fresh query builder instance for the table.
	 *
	 * @return \Illuminate\Database\Query\Builder
	 */
	protected function getQuery()
	{
		return $this->connection->table($this->table);
	}

	/**
	 * Set the existence state for the session.
	 *
	 * @param  bool  $value
	 * @return $this
	 */
	public function setExists($value)
	{
		$this->exists = $value;

		return $this;
	}

}
