<?php

namespace Illuminate\Database\Query;

use Illuminate\Database\Grammar;

/**
 * A dialect-aware extension of Expression that picks
 * the correct raw SQL string for the active connection driver
 * when the grammar compiles the query.
 *
 * @template DValue of literal-string - Drive Name
 * @template TValue of literal-string|int|float
 */
class DialectExpression extends Expression
{
    private const SUPPORTED_DRIVERS = ['mysql', 'mariadb', 'pgsql', 'sqlite', 'sqlsrv', 'default'];

    /**
     * Create a new dialect expression.
     *
     * @param  array<DValue, TValue>  $dialects  Map of driver name => raw SQL.
     *
     * @throws \InvalidArgumentException For unknown driver keys or an empty map.
     */
    public function __construct(protected array $dialects)
    {
        if (empty($dialects)) {
            throw new \InvalidArgumentException(
                'DialectExpression requires at least one dialect entry.'
            );
        }

        $invalidDrivers = array_diff(array_keys($dialects), self::SUPPORTED_DRIVERS);
        if (! empty($invalidDrivers)) {
            throw new \InvalidArgumentException(
                'DialectExpression received unknown driver key(s): ['.implode(', ', $invalidDrivers).']. '.
                'Valid drivers are: '.implode(', ', self::SUPPORTED_DRIVERS).'.'
            );
        }

        // we generate the value based on the driver name at the run time.
        parent::__construct('');
    }

    /**
     * Return the raw SQL for the active connection driver.
     *
     * Overrides Expression::getValue() to return a dialect-specific string
     * instead of the fixed value stored by the parent class.
     *
     * @param  \Illuminate\Database\Grammar  $grammar
     * @return string
     *
     * @throws \RuntimeException For drivers with no mapping and no default.
     */
    public function getValue(Grammar $grammar): string
    {
        $driver = $grammar->getConnection()->getDriverName();

        // get right sql string based on driver name
        if (isset($this->dialects[$driver])) {
            return $this->dialects[$driver];
        }

        // use default sql string if not found the driver
        if (isset($this->dialects['default'])) {
            return $this->dialects['default'];
        }

        // throw exception if not found the driver and default sql string
        throw new \RuntimeException(
            "DialectExpression has no SQL for driver [{$driver}] and no 'default' fallback. ".
            'Registered: ['.implode(', ', array_keys($this->dialects)).'].'
        );
    }
}
