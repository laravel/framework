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
     * Indicates if a lazy hook has been fired.
     *
     * @var bool
     */
    public static $lazilyRefreshed = false;
}
