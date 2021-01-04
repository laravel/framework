<?php

namespace Illuminate\Foundation\Testing;

class RefreshDatabaseState
{
    /**
     * Indicates if the test database has been migrated.
     *
     * @var bool
     */
    public static $migrated = false;

    /**
     * The temporary database name, if any.
     *
     * @var string|null
     */
    public static $temporaryDatabase;
}
