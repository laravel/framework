<?php

namespace Illuminate\Database\Schema\Grammars;

use BackedEnum;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Concerns\CompilesJsonPaths;
use Illuminate\Database\Connection;
use Illuminate\Database\Grammar as BaseGrammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;
use LogicException;
use RuntimeException;

abstract class Grammar extends BaseGrammar
{
    use CompilesJsonPaths;

    /**
     * The possible column modifiers.
     *
     * @var string[]
     */
    protected $modifiers = [];

    /**
     * If this Grammar supports schema changes wrapped in a transaction.
     *
     * @var bool
     */
    protected $transactions = false;

    /**
     * The commands to be executed outside of create or alter command.
     *
     * @var array
     */
    protected $fluentCommands = [];

    /**
     * Compile a create database command.
     *
     * @param  string  $name
     * @param  \Illuminate\Database\Connection  $connection
     * @return void
     *
     * @throws \LogicException
     */
    public function compileCreateDatabase($name, $connection)
    {
        throw new LogicException('This database driver does not support creating databases.');
    }

    /**
     * Compile a drop database if exists command.
     *
     * @param  string  $name
     * @return void
     *
     * @throws \LogicException
     */
    public function compileDropDatabaseIfExists($name)
    {
        throw new LogicException('This database driver does not support dropping databases.');
    }

    /**
     * Compile a rename column command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @param  \Illuminate\Database\Connection  $connection
     * @return array|string
     */
    public function compileRenameColumn(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        return sprintf('alter table %s rename column %s to %s',
            $this->wrapTable($blueprint),
            $this->wrap($command->from),
            $this->wrap($command->to)
        );
    }

    /**
     * Compile a change column command into a series of SQL statements.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @param  \Illuminate\Database\Connection  $connection
     * @return array|string
     *
     * @throws \RuntimeException
     */
    public function compileChange(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        throw new LogicException('This database driver does not support modifying columns.');
    }

    /**
     * Compile a fulltext index key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     *
     * @throws \RuntimeException
     */
    public function compileFulltext(Blueprint $blueprint, Fluent $command)
    {
        throw new RuntimeException('This database driver does not support fulltext index creation.');
    }

    /**
     * Compile a drop fulltext index command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     *
     * @throws \RuntimeException
     */
    public function compileDropFullText(Blueprint $blueprint, Fluent $command)
    {
        throw new RuntimeException('This database driver does not support fulltext index removal.');
    }

    /**
     * Compile a foreign key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileForeign(Blueprint $blueprint, Fluent $command)
    {
        // We need to prepare several of the elements of the foreign key definition
        // before we can create the SQL, such as wrapping the tables and convert
        // an array of columns to comma-delimited strings for the SQL queries.
        $sql = sprintf('alter table %s add constraint %s ',
            $this->wrapTable($blueprint),
            $this->wrap($command->index)
        );

        // Once we have the initial portion of the SQL statement we will add on the
        // key name, table name, and referenced columns. These will complete the
        // main portion of the SQL statement and this SQL will almost be done.
        $sql .= sprintf('foreign key (%s) references %s (%s)',
            $this->columnize($command->columns),
            $this->wrapTable($command->on),
            $this->columnize((array) $command->references)
        );

        // Once we have the basic foreign key creation statement constructed we can
        // build out the syntax for what should happen on an update or delete of
        // the affected columns, which will get something like "cascade", etc.
        if (! is_null($command->onDelete)) {
            $sql .= " on delete {$command->onDelete}";
        }

        if (! is_null($command->onUpdate)) {
            $sql .= " on update {$command->onUpdate}";
        }

        return $sql;
    }

    /**
     * Compile a drop foreign key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropForeign(Blueprint $blueprint, Fluent $command)
    {
        throw new RuntimeException('This database driver does not support dropping foreign keys.');
    }

    /**
     * Compile the blueprint's added column definitions.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @return array
     */
    protected function getColumns(Blueprint $blueprint)
    {
        $columns = [];

        foreach ($blueprint->getAddedColumns() as $column) {
            $columns[] = $this->getColumn($blueprint, $column);
        }

        return $columns;
    }

    /**
     * Compile the column definition.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Database\Schema\ColumnDefinition  $column
     * @return string
     */
    protected function getColumn(Blueprint $blueprint, $column)
    {
        // Each of the column types has their own compiler functions, which are tasked
        // with turning the column definition into its SQL format for this platform
        // used by the connection. The column's modifiers are compiled and added.
        $sql = $this->wrap($column).' '.$this->getType($column);

        return $this->addModifiers($sql, $blueprint, $column);
    }

    /**
     * Get the SQL for the column data type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function getType(Fluent $column)
    {
        return $this->{'type'.ucfirst($column->type)}($column);
    }

    /**
     * Create the column definition for a generated, computed column type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return void
     *
     * @throws \RuntimeException
     */
    protected function typeComputed(Fluent $column)
    {
        throw new RuntimeException('This database driver does not support the computed type.');
    }

    /**
     * Create the column definition for a vector type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function typeVector(Fluent $column)
    {
        throw new RuntimeException('This database driver does not support the vector type.');
    }

    /**
     * Create the column definition for a raw column type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeRaw(Fluent $column)
    {
        return $column->offsetGet('definition');
    }

    /**
     * Add the column modifiers to the definition.
     *
     * @param  string  $sql
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function addModifiers($sql, Blueprint $blueprint, Fluent $column)
    {
        foreach ($this->modifiers as $modifier) {
            if (method_exists($this, $method = "modify{$modifier}")) {
                $sql .= $this->{$method}($blueprint, $column);
            }
        }

        return $sql;
    }

    /**
     * Get the command with a given name if it exists on the blueprint.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  string  $name
     * @return \Illuminate\Support\Fluent|null
     */
    protected function getCommandByName(Blueprint $blueprint, $name)
    {
        $commands = $this->getCommandsByName($blueprint, $name);

        if (count($commands) > 0) {
            return reset($commands);
        }
    }

    /**
     * Get all of the commands with a given name.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  string  $name
     * @return array
     */
    protected function getCommandsByName(Blueprint $blueprint, $name)
    {
        return array_filter($blueprint->getCommands(), function ($value) use ($name) {
            return $value->name == $name;
        });
    }

    /*
     * Determine if a command with a given name exists on the blueprint.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  string  $name
     * @return bool
     */
    protected function hasCommand(Blueprint $blueprint, $name)
    {
        foreach ($blueprint->getCommands() as $command) {
            if ($command->name === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a prefix to an array of values.
     *
     * @param  string  $prefix
     * @param  array  $values
     * @return array
     */
    public function prefixArray($prefix, array $values)
    {
        return array_map(function ($value) use ($prefix) {
            return $prefix.' '.$value;
        }, $values);
    }

    /**
     * Wrap a table in keyword identifiers.
     *
     * @param  mixed  $table
     * @return string
     */
    public function wrapTable($table)
    {
        return parent::wrapTable(
            $table instanceof Blueprint ? $table->getTable() : $table
        );
    }

    /**
     * Wrap a value in keyword identifiers.
     *
     * @param  \Illuminate\Support\Fluent|\Illuminate\Contracts\Database\Query\Expression|string  $value
     * @return string
     */
    public function wrap($value)
    {
        return parent::wrap(
            $value instanceof Fluent ? $value->name : $value,
        );
    }

    /**
     * Format a value so that it can be used in "default" clauses.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function getDefaultValue($value)
    {
        if ($value instanceof Expression) {
            return $this->getValue($value);
        }

        if ($value instanceof BackedEnum) {
            return "'{$value->value}'";
        }

        return is_bool($value)
                    ? "'".(int) $value."'"
                    : "'".(string) $value."'";
    }

    /**
     * Get the fluent commands for the grammar.
     *
     * @return array
     */
    public function getFluentCommands()
    {
        return $this->fluentCommands;
    }

    /**
     * Check if this Grammar supports schema changes wrapped in a transaction.
     *
     * @return bool
     */
    public function supportsSchemaTransactions()
    {
        return $this->transactions;
    }
}
