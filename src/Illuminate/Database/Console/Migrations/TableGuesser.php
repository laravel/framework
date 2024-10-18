<?php

namespace Illuminate\Database\Console\Migrations;

class TableGuesser
{
    const PATTERN = '/^(?<method>create)_(?<table>\w+?)(?:_table)?$|(?J)(?<method>add|drop|change).+_(?:to|from|in)_(?J)(?<table>\w+?)(?:_table)?$/'; 

    /**
     * Attempt to guess the table name and "creation" status of the given migration.
     *
     * @param  string  $migration
     * @return array
     */
    public static function guess($migration)
    {
        if (preg_match(self::PATTERN, $migration, $matches)) {
            return [$matches['table'], $matches['method'] === 'create'];
        }
    }
}
