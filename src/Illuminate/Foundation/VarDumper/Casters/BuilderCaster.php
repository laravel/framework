<?php

namespace Illuminate\Foundation\VarDumper\Casters;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\VarDumper\Properties;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

class BuilderCaster extends Caster
{
    /**
     * @inheritdoc
     */
    protected function cast($target, $properties, $stub, $isNested, $filter = 0)
    {
        $result = new Properties();

        $result->putVirtual('sql', $this->formatSql($target));
        $result->putProtected('connection', $target->getConnection());

        if ($target instanceof EloquentBuilder) {
            $result->copyAndCutProtected('model', $properties);
            $result->copyProtected('eagerLoad', $properties);
        }

        if ($target instanceof Relation) {
            $result->copyAndCutProtected('parent', $properties);
            $result->copyAndCutProtected('related', $properties);
        }

        $result->applyCutsToStub($stub, $properties);

        return $result->all();
    }

    /**
     * Merge the bindings into the SQL statement for easier debugging.
     *
     * @param  \Illuminate\Contracts\Database\Query\Builder  $builder
     * @return string|array
     */
    protected function formatSql($builder)
    {
        $sql = $builder->toSql();
        $bindings = Arr::flatten($builder->getBindings());

        try {
            $pdo = $this->getPdoFromBuilder($builder);

            $formatted = preg_replace_callback('/(?<!\?)\?(?!\?)/', function () use ($pdo, &$bindings) {
                if (0 === count($bindings)) {
                    throw new RuntimeException('Too few bindings.');
                }

                return $pdo->quote(array_shift($bindings));
            }, $sql);

            if (count($bindings)) {
                throw new RuntimeException('Too many bindings.');
            }

            return $formatted;
        } catch (Throwable) {
            return compact('sql', 'bindings');
        }
    }

    /**
     * Get the underlying PDO connection from the Builder instance.
     *
     * @param  \Illuminate\Contracts\Database\Query\Builder  $builder
     * @return \PDO
     */
    protected function getPdoFromBuilder($builder)
    {
        $connection = $builder->getConnection();

        if (! method_exists($connection, 'getPdo')) {
            throw new InvalidArgumentException('Connection does not provide access to PDO connection.');
        }

        $pdo = $connection->getPdo();

        if (! ($pdo instanceof PDO)) {
            throw new InvalidArgumentException('Connection does not have a PDO connection.');
        }

        return $pdo;
    }
}
