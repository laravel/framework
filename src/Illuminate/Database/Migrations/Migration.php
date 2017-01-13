<?php

namespace Illuminate\Database\Migrations;

abstract class Migration
{
    /**
     * The name of the database connection to use.
     *
     * @var string
     */
    protected $connection;

    /**
     * The group of the migration.
     *
     * @var string
     */
    protected $group;

    /**
     * Get the migration connection name.
     *
     * @return string
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get the migration group.
     *
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }
}
