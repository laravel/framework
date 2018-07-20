<?php

namespace Foo\Bar;

use Illuminate\Database\Migrations\MigrationCreator;

/**
 * Class MigrationCreatorSubclass
 */
class MigrationCreatorSubclass extends MigrationCreator
{
    /**
     * Here we override the original method of Laravel 5.6's original firePostCreateHook, with no parameters.
     */
    protected function firePostCreateHooks()
    {
        // Run an arbitrary command
        parent::firePostCreateHooks();
    }
}
