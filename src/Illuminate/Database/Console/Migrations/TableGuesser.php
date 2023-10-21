<?php

namespace Illuminate\Database\Console\Migrations;

class TableGuesser
{
    const CREATE_PATTERNS = [
        '/^create_(\w+)_table$/',
        '/^create_(\w+)$/',
    ];

    const CHANGE_PATTERNS = [
        '/.+_(to|from|in)_(\w+)_table$/' => 2,
        '/.+_(to|from|in)_(\w+)$/' => 2,
        '/^alter_(\w+)_table_.+$/' => 1,
    ];

    /**
     * Attempt to guess the table name and "creation" status of the given migration.
     *
     * @param  string  $migration
     * @return array
     */
    public static function guess($migration)
    {
        foreach (self::CREATE_PATTERNS as $pattern) {
            if (preg_match($pattern, $migration, $matches)) {
                return [$matches[1], $create = true];
            }
        }

        foreach (self::CHANGE_PATTERNS as $pattern => $group) {
            if (preg_match($pattern, $migration, $matches)) {
                return [$matches[$group], $create = false];
            }
        }
    }
}
