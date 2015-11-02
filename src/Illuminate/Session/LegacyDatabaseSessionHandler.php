<?php

namespace Illuminate\Session;

use SessionHandlerInterface;
use Illuminate\Database\ConnectionInterface;

/**
 * @deprecated since version 5.2. Use Illuminate\Session\DatabaseSessionHandler.
 */
class LegacyDatabaseSessionHandler implements SessionHandlerInterface, ExistenceAwareInterface
{
    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\ConnectionInterface
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
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  string  $table
     * @return void
     */
    public function __construct(ConnectionInterface $connection, $table)
    {
        $this->table = $table;
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        $session = (object) $this->getQuery()->find($sessionId);

        if (isset($session->payload)) {
            $this->exists = true;

            return base64_decode($session->payload);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        if ($this->exists) {
            $this->getQuery()->where('id', $sessionId)->update([
                'payload' => base64_encode($data), 'last_activity' => time(),
            ]);
        } else {
            $this->getQuery()->insert([
                'id' => $sessionId, 'payload' => base64_encode($data), 'last_activity' => time(),
            ]);
        }

        $this->exists = true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        $this->getQuery()->where('id', $sessionId)->delete();
    }

    /**
     * {@inheritdoc}
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
