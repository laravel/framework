<?php

namespace Illuminate\Database\Migrations;

use Exception;

/**
 * Exception to be thrown in the down() methods of migrations that signifies it
 * is an irreversible migration and stops execution.
 */
class IrreversibleMigrationException extends Exception
{
}
