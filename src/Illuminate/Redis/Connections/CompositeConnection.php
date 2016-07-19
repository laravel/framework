<?php

namespace Illuminate\Redis\Connections;

use Predis\Connection\ConnectionException;
use Predis\Connection\CompositeConnectionInterface;

class CompositeConnection extends NodeConnection implements CompositeConnectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getProtocol()
    {
        return $this->connection->getProtocol();
    }

    /**
     * {@inheritdoc}
     */
    public function writeBuffer($buffer)
    {
        try {
            return $this->connection->writeBuffer($buffer);
        } catch (ConnectionException $e) {
            return $this->connection->writeBuffer($buffer);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function readBuffer($length)
    {
        try {
            return $this->connection->readBuffer($length);
        } catch (ConnectionException $e) {
            return $this->connection->readBuffer($length);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function readLine()
    {
        try {
            return $this->connection->readLine();
        } catch (ConnectionException $e) {
            return $this->connection->readLine();
        }
    }
}
