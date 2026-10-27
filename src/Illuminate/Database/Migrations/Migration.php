<?php

namespace Illuminate\Database\Migrations;

abstract class Migration
{
    /**
     * Indicates if generated migrations should omit the down method.
     *
     * @var bool
     */
    public static $withoutDownMethod = false;

    /**
     * The name of the database connection to use.
     *
     * @var string|null
     */
    protected $connection;

    /**
     * Enables, if supported, wrapping the migration within a transaction.
     *
     * @var bool
     */
    public $withinTransaction = true;

    /**
     * Configure generated migrations to omit the down method.
     *
     * @return void
     */
    public static function withoutDownMethod(bool $withoutDownMethod = true)
    {
        static::$withoutDownMethod = $withoutDownMethod;
    }

    /**
     * Get the migration connection name.
     *
     * @return string|null
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Determine if this migration should run.
     *
     * @return bool
     */
    public function shouldRun(): bool
    {
        return true;
    }
}
