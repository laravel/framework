<?php

namespace Illuminate\Foundation\Testing;

class SeedDatabaseState
{
    /**
     * Indicates if the test database has been seeded.
     *
     * @var bool
     */
    public static $seeded = false;

    /**
     * Indicates if the seeders should run once at the beginning of the suite.
     *
     * @var bool
     */
    public static $seedOnce = true;

    /**
     * Runs only these registered seeders instead of running all seeders.
     *
     * @var array
     */
    public static $seeders = [];
}
