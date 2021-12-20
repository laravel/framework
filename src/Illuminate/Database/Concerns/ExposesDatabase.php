<?php

namespace Illuminate\Database\Concerns;

trait ExposesDatabase
{
    /**
     * Get the grammar's database driver name.
     *
     * @return string
     */
    public function getDatabaseDriver()
    {
        return $this->connection->getDriverName();
    }

    /**
     * Get the grammar's database version.
     *
     * @return string
     */
    public function getDatabaseVersion()
    {
        return $this->connection->getDatabaseVersion();
    }
}
