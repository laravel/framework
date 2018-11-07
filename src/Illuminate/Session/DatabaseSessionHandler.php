<?php namespace Illuminate\Session;

use Illuminate\Database\Connection;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;

class DatabaseSessionHandler implements \SessionHandlerInterface, ExistenceAwareInterface {

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
		$session = (object) $this->getQuery()->find($sessionId);

		if (isset($session->payload))
		{
			$this->exists = true;

			return base64_decode($session->payload);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function write($sessionId, $data)
	{
		$payload = $this->getDefaultPayload($data);
		
		if (! $this->exists) {
            $this->read($sessionId);
        }
		
		if ($this->exists)
		{
			$this->performUpdate($sessionId, $payload);
		}
		else
		{
			$this->performInsert($sessionId, $payload);
		}

		$this->exists = true;
	}
/**
     * Perform an insert operation on the session ID.
     *
     * @param  string  $sessionId
     * @param  string  $payload
     * @return void
     */
    protected function performInsert($sessionId, $payload)
    {
        try {
            return $this->getQuery()->insert(Arr::set($payload, 'id', $sessionId));
        } catch (QueryException $e) {
            $this->performUpdate($sessionId, $payload);
        }
    }

    /**
     * Perform an update operation on the session ID.
     *
     * @param  string  $sessionId
     * @param  string  $payload
     * @return int
     */
    protected function performUpdate($sessionId, $payload)
    {
        return $this->getQuery()->where('id', $sessionId)->update($payload);
    }
	
    /**
     * Get the default payload for the session.
     *
     * @param  string  $data
     * @return array
     */
	protected function getDefaultPayload($data)
    {
		//timestamp was time() in 4.2 origionally
        $payload = [
            'payload' => base64_encode($data),
            'last_activity' => Carbon::now()->getTimestamp(),
        ];
		//just return the payload
        return $payload;
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
