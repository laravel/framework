<?php namespace Illuminate\Session;

use Illuminate\Database\Connection;
use Illuminate\Encryption\Encrypter;
use Symfony\Component\HttpFoundation\Response;

class DatabaseStore extends Store implements Sweeper {

	/**
	 * The database connection instance.
	 *
	 * @var \Illuminate\Database\Connection
	 */
	protected $connection;

	/**
	 * The encrypter instance.
	 *
	 * @var \Illuminate\Encrypter
	 */
	protected $encrypter;

	/**
	 * The session table name.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Create a new database session store.
	 *
	 * @param  \Illuminate\Database\Connection  $connection
	 * @param  \Illuminate\Encrypter  $encrypter
	 * @param  string  $table
	 * @return void
	 */
	public function __construct(Connection $connection, Encrypter $encrypter, $table)
	{
		$this->table = $table;
		$this->encrypter = $encrypter;
		$this->connection = $connection;
	}

	/**
	 * Retrieve a session payload from storage.
	 *
	 * @param  string  $id
	 * @return array|null
	 */
	public function retrieveSession($id)
	{
		$session = $this->table()->find($id);

		if ( ! is_null($session))
		{
			if (is_array($session)) $session = (object) $session;

			return $this->encrypter->decrypt($session->payload);
		}
	}

	/**
	 * Create a new session in storage.
	 *
	 * @param  string  $id
	 * @param  array   $session
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	public function createSession($id, array $session, Response $response)
	{
		$payload = $this->encrypter->encrypt($session);

		$last_activity = $session['last_activity'];

		$this->table()->insert(compact('id', 'payload', 'last_activity'));
	}

	/**
	 * Update an existing session in storage.
	 *
	 * @param  string  $id
	 * @param  array   $session
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	public function updateSession($id, array $session, Response $response)
	{
		$payload = $this->encrypter->encrypt($session);

		$last_activity = $session['last_activity'];

		$this->table()->where('id', '=', $id)->update(compact('payload', 'last_activity'));
	}

	/**
	 * Remove session records older than a given expiration.
	 *
	 * @param  int   $expiration
	 * @return void
	 */
	public function sweep($expiration)
	{
		$this->table()->where('last_activity', '<', $expiration)->delete();
	}

	/**
	 * Get a query builder instance for the table.
	 *
	 * @return \Illuminate\Database\Query\Builder
	 */
	protected function table()
	{
		return $this->connection->table($this->table);
	}

	/**
	 * Get the database connection instance.
	 *
	 * @return \Illuminate\Database\Connection
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 * Get the encrypter instance.
	 *
	 * @return \Illuminate\Encrypter
	 */
	public function getEncrypter()
	{
		return $this->encrypter;
	}

}