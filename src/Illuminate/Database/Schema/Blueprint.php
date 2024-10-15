<?php

namespace Illuminate\Database\Schema;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Support\Fluent;
use Illuminate\Support\Traits\Macroable;

class Blueprint
{
    use Macroable;

    /**
     * The table the blueprint describes.
     *
     * @var string
     */
    protected $table;

    /**
     * The prefix of the table.
     *
     * @var string
     */
    protected $prefix;

    /**
     * The columns that should be added to the table.
     *
     * @var \Illuminate\Database\Schema\ColumnDefinition[]
     */
    protected $columns = [];

    /**
     * The commands that should be run for the table.
     *
     * @var \Illuminate\Support\Fluent[]
     */
    protected $commands = [];

    /**
     * The storage engine that should be used for the table.
     *
     * @var string
     */
    public $engine;

    /**
     * The default character set that should be used for the table.
     *
     * @var string
     */
    public $charset;

    /**
     * The collation that should be used for the table.
     *
     * @var string
     */
    public $collation;

    /**
     * Whether to make the table temporary.
     *
     * @var bool
     */
    public $temporary = false;

    /**
     * The column to add new columns after.
     *
     * @var string
     */
    public $after;

    /**
     * The blueprint state instance.
     *
     * @var \Illuminate\Database\Schema\BlueprintState|null
     */
    protected $state;

    /**
     * Create a new schema blueprint.
     *
     * @param  string  $table
     * @param  \Closure|null  $callback
     * @param  string  $prefix
     * @return void
     */
    public function __construct($table, ?Closure $callback = null, $prefix = '')
    {
        $this->table = $table;
        $this->prefix = $prefix;

        if (! is_null($callback)) {
            $callback($this);
        }
    }

    /**
     * Execute the blueprint against the database.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return void
     */
    public function build(Connection $connection, Grammar $grammar)
    {
        foreach ($this->toSql($connection, $grammar) as $statement) {
            $connection->statement($statement);
        }
    }

    /**
     * Get the raw SQL statements for the blueprint.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return array
     */
    public function toSql(Connection $connection, Grammar $grammar)
    {
        $this->addImpliedCommands($connection, $grammar);

        $statements = [];

        // Each type of command has a corresponding compiler function on the schema
        // grammar which is used to build the necessary SQL statements to build
        // the blueprint element, so we'll just call that compilers function.
        $this->ensureCommandsAreValid($connection);

        foreach ($this->commands as $command) {
            if ($command->shouldBeSkipped) {
                continue;
            }

            $method = 'compile'.ucfirst($command->name);

            if (method_exists($grammar, $method) || $grammar::hasMacro($method)) {
                if ($this->hasState()) {
                    $this->state->update($command);
                }

                if (! is_null($sql = $grammar->$method($this, $command, $connection))) {
                    $statements = array_merge($statements, (array) $sql);
                }
            }
        }

        return $statements;
    }

    /**
     * Ensure the commands on the blueprint are valid for the connection type.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return void
     *
     * @throws \BadMethodCallException
     */
    protected function ensureCommandsAreValid(Connection $connection)
    {
        //
    }

    /**
     * Get all of the commands matching the given names.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  array  $names
     * @return \Illuminate\Support\Collection
     */
    protected function commandsNamed(array $names)
    {
        return collect($this->commands)->filter(function ($command) use ($names) {
            return in_array($command->name, $names);
        });
    }

    /**
     * Add the commands that are implied by the blueprint's state.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return void
     */
    protected function addImpliedCommands(Connection $connection, Grammar $grammar)
    {
        $this->addFluentIndexes($connection, $grammar);

        $this->addFluentCommands($connection, $grammar);

        if (! $this->creating()) {
            $this->commands = array_map(
                fn ($command) => $command instanceof ColumnDefinition
                    ? $this->createCommand($command->change ? 'change' : 'add', ['column' => $command])
                    : $command,
                $this->commands
            );

            $this->addAlterCommands($connection, $grammar);
        }
    }

    /**
     * Add the index commands fluently specified on columns.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return void
     */
    protected function addFluentIndexes(Connection $connection, Grammar $grammar)
    {
        foreach ($this->columns as $column) {
            foreach (['primary', 'unique', 'index', 'fulltext', 'fullText', 'spatialIndex'] as $index) {
                // If the column is supposed to be changed to an auto increment column and
                // the specified index is primary, there is no need to add a command on
                // MySQL, as it will be handled during the column definition instead.
                if ($index === 'primary' && $column->autoIncrement && $column->change && $grammar instanceof MySqlGrammar) {
                    continue 2;
                }

                // If the index has been specified on the given column, but is simply equal
                // to "true" (boolean), no name has been specified for this index so the
                // index method can be called without a name and it will generate one.
                if ($column->{$index} === true) {
                    $this->{$index}($column->name);
                    $column->{$index} = null;

                    continue 2;
                }

                // If the index has been specified on the given column, but it equals false
                // and the column is supposed to be changed, we will call the drop index
                // method with an array of column to drop it by its conventional name.
                elseif ($column->{$index} === false && $column->change) {
                    $this->{'drop'.ucfirst($index)}([$column->name]);
                    $column->{$index} = null;

                    continue 2;
                }

                // If the index has been specified on the given column, and it has a string
                // value, we'll go ahead and call the index method and pass the name for
                // the index since the developer specified the explicit name for this.
                elseif (isset($column->{$index})) {
                    $this->{$index}($column->name, $column->{$index});
                    $column->{$index} = null;

                    continue 2;
                }
            }
        }
    }

    /**
     * Add the fluent commands specified on any columns.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return void
     */
    public function addFluentCommands(Connection $connection, Grammar $grammar)
    {
        foreach ($this->columns as $column) {
            foreach ($grammar->getFluentCommands() as $commandName) {
                $this->addCommand($commandName, compact('column'));
            }
        }
    }

    /**
     * Add the alter commands if whenever needed.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return void
     */
    public function addAlterCommands(Connection $connection, Grammar $grammar)
    {
        if (! $grammar instanceof SQLiteGrammar) {
            return;
        }

        $alterCommands = $grammar->getAlterCommands($connection);

        [$commands, $lastCommandWasAlter, $hasAlterCommand] = [
            [], false, false,
        ];

        foreach ($this->commands as $command) {
            if (in_array($command->name, $alterCommands)) {
                $hasAlterCommand = true;
                $lastCommandWasAlter = true;
            } elseif ($lastCommandWasAlter) {
                $commands[] = $this->createCommand('alter');
                $lastCommandWasAlter = false;
            }

            $commands[] = $command;
        }

        if ($lastCommandWasAlter) {
            $commands[] = $this->createCommand('alter');
        }

        if ($hasAlterCommand) {
            $this->state = new BlueprintState($this, $connection, $grammar);
        }

        $this->commands = $commands;
    }

    /**
     * Determine if the blueprint has a create command.
     *
     * @return bool
     */
    public function creating()
    {
        return collect($this->commands)->contains(function ($command) {
            return ! $command instanceof ColumnDefinition && $command->name === 'create';
        });
    }

    /**
     * Indicate that the table needs to be created.
     *
     * @return \Illuminate\Support\Fluent
     */
    public function create()
    {
        return $this->addCommand('create');
    }

    /**
     * Specify the storage engine that should be used for the table.
     *
     * @param  string  $engine
     * @return void
     */
    public function engine($engine)
    {
        $this->engine = $engine;
    }

    /**
     * Specify that the InnoDB storage engine should be used for the table (MySQL only).
     *
     * @return void
     */
    public function innoDb()
    {
        $this->engine('InnoDB');
    }

    /**
     * Specify the character set that should be used for the table.
     *
     * @param  string  $charset
     * @return void
     */
    public function charset($charset)
    {
        $this->charset = $charset;
    }

    /**
     * Specify the collation that should be used for the table.
     *
     * @param  string  $collation
     * @return void
     */
    public function collation($collation)
    {
        $this->collation = $collation;
    }

    /**
     * Indicate that the table needs to be temporary.
     *
     * @return void
     */
    public function temporary()
    {
        $this->temporary = true;
    }

    /**
     * Indicate that the table should be dropped.
     *
     * @return \Illuminate\Support\Fluent
     */
    public function drop()
    {
        return $this->addCommand('drop');
    }

    /**
     * Indicate that the table should be dropped if it exists.
     *
     * @return \Illuminate\Support\Fluent
     */
    public function dropIfExists()
    {
        return $this->addCommand('dropIfExists');
    }

    /**
     * Indicate that the given columns should be dropped.
     *
     * @param  array|mixed  $columns
     * @return \Illuminate\Support\Fluent
     */
    public function dropColumn($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        return $this->addCommand('dropColumn', compact('columns'));
    }

    /**
     * Indicate that the given columns should be renamed.
     *
     * @param  string  $from
     * @param  string  $to
     * @return \Illuminate\Support\Fluent
     */
    public function renameColumn($from, $to)
    {
        return $this->addCommand('renameColumn', compact('from', 'to'));
    }

    /**
     * Indicate that the given primary key should be dropped.
     *
     * @param  string|array|null  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropPrimary($index = null)
    {
        return $this->dropIndexCommand('dropPrimary', 'primary', $index);
    }

    /**
     * Indicate that the given unique key should be dropped.
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropUnique($index)
    {
        return $this->dropIndexCommand('dropUnique', 'unique', $index);
    }

    /**
     * Indicate that the given index should be dropped.
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropIndex($index)
    {
        return $this->dropIndexCommand('dropIndex', 'index', $index);
    }

    /**
     * Indicate that the given fulltext index should be dropped.
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropFullText($index)
    {
        return $this->dropIndexCommand('dropFullText', 'fulltext', $index);
    }

    /**
     * Indicate that the given spatial index should be dropped.
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropSpatialIndex($index)
    {
        return $this->dropIndexCommand('dropSpatialIndex', 'spatialIndex', $index);
    }

    /**
     * Indicate that the given foreign key should be dropped.
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropForeign($index)
    {
        return $this->dropIndexCommand('dropForeign', 'foreign', $index);
    }

    /**
     * Indicate that the given column and foreign key should be dropped.
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function dropConstrainedForeignId($column)
    {
        $this->dropForeign([$column]);

        return $this->dropColumn($column);
    }

    /**
     * Indicate that the given foreign key should be dropped.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @param  string|null  $column
     * @return \Illuminate\Support\Fluent
     */
    public function dropForeignIdFor($model, $column = null)
    {
        if (is_string($model)) {
            $model = new $model;
        }

        return $this->dropForeign([$column ?: $model->getForeignKey()]);
    }

    /**
     * Indicate that the given foreign key should be dropped.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @param  string|null  $column
     * @return \Illuminate\Support\Fluent
     */
    public function dropConstrainedForeignIdFor($model, $column = null)
    {
        if (is_string($model)) {
            $model = new $model;
        }

        return $this->dropConstrainedForeignId($column ?: $model->getForeignKey());
    }

    /**
     * Indicate that the given indexes should be renamed.
     *
     * @param  string  $from
     * @param  string  $to
     * @return \Illuminate\Support\Fluent
     */
    public function renameIndex($from, $to)
    {
        return $this->addCommand('renameIndex', compact('from', 'to'));
    }

    /**
     * Indicate that the timestamp columns should be dropped.
     *
     * @return void
     */
    public function dropTimestamps()
    {
        $this->dropColumn('created_at', 'updated_at');
    }

    /**
     * Indicate that the timestamp columns should be dropped.
     *
     * @return void
     */
    public function dropTimestampsTz()
    {
        $this->dropTimestamps();
    }

    /**
     * Indicate that the soft delete column should be dropped.
     *
     * @param  string  $column
     * @return void
     */
    public function dropSoftDeletes($column = 'deleted_at')
    {
        $this->dropColumn($column);
    }

    /**
     * Indicate that the soft delete column should be dropped.
     *
     * @param  string  $column
     * @return void
     */
    public function dropSoftDeletesTz($column = 'deleted_at')
    {
        $this->dropSoftDeletes($column);
    }

    /**
     * Indicate that the remember token column should be dropped.
     *
     * @return void
     */
    public function dropRememberToken()
    {
        $this->dropColumn('remember_token');
    }

    /**
     * Indicate that the polymorphic columns should be dropped.
     *
     * @param  string  $name
     * @param  string|null  $indexName
     * @return void
     */
    public function dropMorphs($name, $indexName = null)
    {
        $this->dropIndex($indexName ?: $this->createIndexName('index', ["{$name}_type", "{$name}_id"]));

        $this->dropColumn("{$name}_type", "{$name}_id");
    }

    /**
     * Rename the table to a given name.
     *
     * @param  string  $to
     * @return \Illuminate\Support\Fluent
     */
    public function rename($to)
    {
        return $this->addCommand('rename', compact('to'));
    }

    /**
     * Specify the primary key(s) for the table.
     *
     * @param  string|array  $columns
     * @param  string|null  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Database\Schema\IndexDefinition
     */
    public function primary($columns, $name = null, $algorithm = null)
    {
        return $this->indexCommand('primary', $columns, $name, $algorithm);
    }

    /**
     * Specify a unique index for the table.
     *
     * @param  string|array  $columns
     * @param  string|null  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Database\Schema\IndexDefinition
     */
    public function unique($columns, $name = null, $algorithm = null)
    {
        return $this->indexCommand('unique', $columns, $name, $algorithm);
    }

    /**
     * Specify an index for the table.
     *
     * @param  string|array  $columns
     * @param  string|null  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Database\Schema\IndexDefinition
     */
    public function index($columns, $name = null, $algorithm = null)
    {
        return $this->indexCommand('index', $columns, $name, $algorithm);
    }

    /**
     * Specify an fulltext for the table.
     *
     * @param  string|array  $columns
     * @param  string|null  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Database\Schema\IndexDefinition
     */
    public function fullText($columns, $name = null, $algorithm = null)
    {
        return $this->indexCommand('fulltext', $columns, $name, $algorithm);
    }

    /**
     * Specify a spatial index for the table.
     *
     * @param  string|array  $columns
     * @param  string|null  $name
     * @return \Illuminate\Database\Schema\IndexDefinition
     */
    public function spatialIndex($columns, $name = null)
    {
        return $this->indexCommand('spatialIndex', $columns, $name);
    }

    /**
     * Specify a raw index for the table.
     *
     * @param  string  $expression
     * @param  string  $name
     * @return \Illuminate\Database\Schema\IndexDefinition
     */
    public function rawIndex($expression, $name)
    {
        return $this->index([new Expression($expression)], $name);
    }

    /**
     * Specify a foreign key for the table.
     *
     * @param  string|array  $columns
     * @param  string|null  $name
     * @return \Illuminate\Database\Schema\ForeignKeyDefinition
     */
    public function foreign($columns, $name = null)
    {
        $command = new ForeignKeyDefinition(
            $this->indexCommand('foreign', $columns, $name)->getAttributes()
        );

        $this->commands[count($this->commands) - 1] = $command;

        return $command;
    }

    /**
     * Create a new auto-incrementing big integer (8-byte) column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function id($column = 'id')
    {
        return $this->bigIncrements($column);
    }

    /**
     * Create a new auto-incrementing integer (4-byte) column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function increments($column)
    {
        return $this->unsignedInteger($column, true);
    }

    /**
     * Create a new auto-incrementing integer (4-byte) column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function integerIncrements($column)
    {
        return $this->unsignedInteger($column, true);
    }

    /**
     * Create a new auto-incrementing tiny integer (1-byte) column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function tinyIncrements($column)
    {
        return $this->unsignedTinyInteger($column, true);
    }

    /**
     * Create a new auto-incrementing small integer (2-byte) column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function smallIncrements($column)
    {
        return $this->unsignedSmallInteger($column, true);
    }

    /**
     * Create a new auto-incrementing medium integer (3-byte) column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function mediumIncrements($column)
    {
        return $this->unsignedMediumInteger($column, true);
    }

    /**
     * Create a new auto-incrementing big integer (8-byte) column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function bigIncrements($column)
    {
        return $this->unsignedBigInteger($column, true);
    }

    /**
     * Create a new char column on the table.
     *
     * @param  string  $column
     * @param  int|null  $length
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function char($column, $length = null)
    {
        $length = ! is_null($length) ? $length : Builder::$defaultStringLength;

        return $this->addColumn('char', $column, compact('length'));
    }

    /**
     * Create a new string column on the table.
     *
     * @param  string  $column
     * @param  int|null  $length
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function string($column, $length = null)
    {
        $length = $length ?: Builder::$defaultStringLength;

        return $this->addColumn('string', $column, compact('length'));
    }

    /**
     * Create a new tiny text column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function tinyText($column)
    {
        return $this->addColumn('tinyText', $column);
    }

    /**
     * Create a new text column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function text($column)
    {
        return $this->addColumn('text', $column);
    }

    /**
     * Create a new medium text column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function mediumText($column)
    {
        return $this->addColumn('mediumText', $column);
    }

    /**
     * Create a new long text column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function longText($column)
    {
        return $this->addColumn('longText', $column);
    }

    /**
     * Create a new integer (4-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function integer($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('integer', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new tiny integer (1-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function tinyInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('tinyInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new small integer (2-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function smallInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('smallInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new medium integer (3-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function mediumInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('mediumInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new big integer (8-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function bigInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('bigInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new unsigned integer (4-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function unsignedInteger($column, $autoIncrement = false)
    {
        return $this->integer($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned tiny integer (1-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function unsignedTinyInteger($column, $autoIncrement = false)
    {
        return $this->tinyInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned small integer (2-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function unsignedSmallInteger($column, $autoIncrement = false)
    {
        return $this->smallInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned medium integer (3-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function unsignedMediumInteger($column, $autoIncrement = false)
    {
        return $this->mediumInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned big integer (8-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function unsignedBigInteger($column, $autoIncrement = false)
    {
        return $this->bigInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned big integer (8-byte) column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ForeignIdColumnDefinition
     */
    public function foreignId($column)
    {
        return $this->addColumnDefinition(new ForeignIdColumnDefinition($this, [
            'type' => 'bigInteger',
            'name' => $column,
            'autoIncrement' => false,
            'unsigned' => true,
        ]));
    }

    /**
     * Create a foreign ID column for the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @param  string|null  $column
     * @return \Illuminate\Database\Schema\ForeignIdColumnDefinition
     */
    public function foreignIdFor($model, $column = null)
    {
        if (is_string($model)) {
            $model = new $model;
        }

        $column = $column ?: $model->getForeignKey();

        if ($model->getKeyType() === 'int' && $model->getIncrementing()) {
            return $this->foreignId($column)->table($model->getTable());
        }

        $modelTraits = class_uses_recursive($model);

        if (in_array(HasUlids::class, $modelTraits, true)) {
            return $this->foreignUlid($column, 26)->table($model->getTable());
        }

        return $this->foreignUuid($column)->table($model->getTable());
    }

    /**
     * Create a new float column on the table.
     *
     * @param  string  $column
     * @param  int  $precision
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function float($column, $precision = 53)
    {
        return $this->addColumn('float', $column, compact('precision'));
    }

    /**
     * Create a new double column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function double($column)
    {
        return $this->addColumn('double', $column);
    }

    /**
     * Create a new decimal column on the table.
     *
     * @param  string  $column
     * @param  int  $total
     * @param  int  $places
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function decimal($column, $total = 8, $places = 2)
    {
        return $this->addColumn('decimal', $column, compact('total', 'places'));
    }

    /**
     * Create a new boolean column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function boolean($column)
    {
        return $this->addColumn('boolean', $column);
    }

    /**
     * Create a new enum column on the table.
     *
     * @param  string  $column
     * @param  array  $allowed
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function enum($column, array $allowed)
    {
        return $this->addColumn('enum', $column, compact('allowed'));
    }

    /**
     * Create a new set column on the table.
     *
     * @param  string  $column
     * @param  array  $allowed
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function set($column, array $allowed)
    {
        return $this->addColumn('set', $column, compact('allowed'));
    }

    /**
     * Create a new json column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function json($column)
    {
        return $this->addColumn('json', $column);
    }

    /**
     * Create a new jsonb column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function jsonb($column)
    {
        return $this->addColumn('jsonb', $column);
    }

    /**
     * Create a new date column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function date($column)
    {
        return $this->addColumn('date', $column);
    }

    /**
     * Create a new date-time column on the table.
     *
     * @param  string  $column
     * @param  int|null  $precision
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function dateTime($column, $precision = 0)
    {
        return $this->addColumn('dateTime', $column, compact('precision'));
    }

    /**
     * Create a new date-time column (with time zone) on the table.
     *
     * @param  string  $column
     * @param  int|null  $precision
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function dateTimeTz($column, $precision = 0)
    {
        return $this->addColumn('dateTimeTz', $column, compact('precision'));
    }

    /**
     * Create a new time column on the table.
     *
     * @param  string  $column
     * @param  int|null  $precision
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function time($column, $precision = 0)
    {
        return $this->addColumn('time', $column, compact('precision'));
    }

    /**
     * Create a new time column (with time zone) on the table.
     *
     * @param  string  $column
     * @param  int|null  $precision
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function timeTz($column, $precision = 0)
    {
        return $this->addColumn('timeTz', $column, compact('precision'));
    }

    /**
     * Create a new timestamp column on the table.
     *
     * @param  string  $column
     * @param  int|null  $precision
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function timestamp($column, $precision = 0)
    {
        return $this->addColumn('timestamp', $column, compact('precision'));
    }

    /**
     * Create a new timestamp (with time zone) column on the table.
     *
     * @param  string  $column
     * @param  int|null  $precision
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function timestampTz($column, $precision = 0)
    {
        return $this->addColumn('timestampTz', $column, compact('precision'));
    }

    /**
     * Add nullable creation and update timestamps to the table.
     *
     * @param  int|null  $precision
     * @return void
     */
    public function timestamps($precision = 0)
    {
        $this->timestamp('created_at', $precision)->nullable();

        $this->timestamp('updated_at', $precision)->nullable();
    }

    /**
     * Add nullable creation and update timestamps to the table.
     *
     * Alias for self::timestamps().
     *
     * @param  int|null  $precision
     * @return void
     */
    public function nullableTimestamps($precision = 0)
    {
        $this->timestamps($precision);
    }

    /**
     * Add creation and update timestampTz columns to the table.
     *
     * @param  int|null  $precision
     * @return void
     */
    public function timestampsTz($precision = 0)
    {
        $this->timestampTz('created_at', $precision)->nullable();

        $this->timestampTz('updated_at', $precision)->nullable();
    }

    /**
     * Add creation and update datetime columns to the table.
     *
     * @param  int|null  $precision
     * @return void
     */
    public function datetimes($precision = 0)
    {
        $this->datetime('created_at', $precision)->nullable();

        $this->datetime('updated_at', $precision)->nullable();
    }

    /**
     * Add a "deleted at" timestamp for the table.
     *
     * @param  string  $column
     * @param  int|null  $precision
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function softDeletes($column = 'deleted_at', $precision = 0)
    {
        return $this->timestamp($column, $precision)->nullable();
    }

    /**
     * Add a "deleted at" timestampTz for the table.
     *
     * @param  string  $column
     * @param  int|null  $precision
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function softDeletesTz($column = 'deleted_at', $precision = 0)
    {
        return $this->timestampTz($column, $precision)->nullable();
    }

    /**
     * Add a "deleted at" datetime column to the table.
     *
     * @param  string  $column
     * @param  int|null  $precision
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function softDeletesDatetime($column = 'deleted_at', $precision = 0)
    {
        return $this->datetime($column, $precision)->nullable();
    }

    /**
     * Create a new year column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function year($column)
    {
        return $this->addColumn('year', $column);
    }

    /**
     * Create a new binary column on the table.
     *
     * @param  string  $column
     * @param  int|null  $length
     * @param  bool  $fixed
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function binary($column, $length = null, $fixed = false)
    {
        return $this->addColumn('binary', $column, compact('length', 'fixed'));
    }

    /**
     * Create a new UUID column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function uuid($column = 'uuid')
    {
        return $this->addColumn('uuid', $column);
    }

    /**
     * Create a new UUID column on the table with a foreign key constraint.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ForeignIdColumnDefinition
     */
    public function foreignUuid($column)
    {
        return $this->addColumnDefinition(new ForeignIdColumnDefinition($this, [
            'type' => 'uuid',
            'name' => $column,
        ]));
    }

    /**
     * Create a new ULID column on the table.
     *
     * @param  string  $column
     * @param  int|null  $length
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function ulid($column = 'ulid', $length = 26)
    {
        return $this->char($column, $length);
    }

    /**
     * Create a new ULID column on the table with a foreign key constraint.
     *
     * @param  string  $column
     * @param  int|null  $length
     * @return \Illuminate\Database\Schema\ForeignIdColumnDefinition
     */
    public function foreignUlid($column, $length = 26)
    {
        return $this->addColumnDefinition(new ForeignIdColumnDefinition($this, [
            'type' => 'char',
            'name' => $column,
            'length' => $length,
        ]));
    }

    /**
     * Create a new IP address column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function ipAddress($column = 'ip_address')
    {
        return $this->addColumn('ipAddress', $column);
    }

    /**
     * Create a new MAC address column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function macAddress($column = 'mac_address')
    {
        return $this->addColumn('macAddress', $column);
    }

    /**
     * Create a new geometry column on the table.
     *
     * @param  string  $column
     * @param  string|null  $subtype
     * @param  int  $srid
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function geometry($column, $subtype = null, $srid = 0)
    {
        return $this->addColumn('geometry', $column, compact('subtype', 'srid'));
    }

    /**
     * Create a new geography column on the table.
     *
     * @param  string  $column
     * @param  string|null  $subtype
     * @param  int  $srid
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function geography($column, $subtype = null, $srid = 4326)
    {
        return $this->addColumn('geography', $column, compact('subtype', 'srid'));
    }

    /**
     * Create a new generated, computed column on the table.
     *
     * @param  string  $column
     * @param  string  $expression
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function computed($column, $expression)
    {
        return $this->addColumn('computed', $column, compact('expression'));
    }

    /**
     * Create a new vector column on the table.
     *
     * @param  string  $column
     * @param  int  $dimensions
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function vector($column, $dimensions)
    {
        return $this->addColumn('vector', $column, compact('dimensions'));
    }

    /**
     * Add the proper columns for a polymorphic table.
     *
     * @param  string  $name
     * @param  string|null  $indexName
     * @return void
     */
    public function morphs($name, $indexName = null)
    {
        if (Builder::$defaultMorphKeyType === 'uuid') {
            $this->uuidMorphs($name, $indexName);
        } elseif (Builder::$defaultMorphKeyType === 'ulid') {
            $this->ulidMorphs($name, $indexName);
        } else {
            $this->numericMorphs($name, $indexName);
        }
    }

    /**
     * Add nullable columns for a polymorphic table.
     *
     * @param  string  $name
     * @param  string|null  $indexName
     * @return void
     */
    public function nullableMorphs($name, $indexName = null)
    {
        if (Builder::$defaultMorphKeyType === 'uuid') {
            $this->nullableUuidMorphs($name, $indexName);
        } elseif (Builder::$defaultMorphKeyType === 'ulid') {
            $this->nullableUlidMorphs($name, $indexName);
        } else {
            $this->nullableNumericMorphs($name, $indexName);
        }
    }

    /**
     * Add the proper columns for a polymorphic table using numeric IDs (incremental).
     *
     * @param  string  $name
     * @param  string|null  $indexName
     * @return void
     */
    public function numericMorphs($name, $indexName = null)
    {
        $this->string("{$name}_type");

        $this->unsignedBigInteger("{$name}_id");

        $this->index(["{$name}_type", "{$name}_id"], $indexName);
    }

    /**
     * Add nullable columns for a polymorphic table using numeric IDs (incremental).
     *
     * @param  string  $name
     * @param  string|null  $indexName
     * @return void
     */
    public function nullableNumericMorphs($name, $indexName = null)
    {
        $this->string("{$name}_type")->nullable();

        $this->unsignedBigInteger("{$name}_id")->nullable();

        $this->index(["{$name}_type", "{$name}_id"], $indexName);
    }

    /**
     * Add the proper columns for a polymorphic table using UUIDs.
     *
     * @param  string  $name
     * @param  string|null  $indexName
     * @return void
     */
    public function uuidMorphs($name, $indexName = null)
    {
        $this->string("{$name}_type");

        $this->uuid("{$name}_id");

        $this->index(["{$name}_type", "{$name}_id"], $indexName);
    }

    /**
     * Add nullable columns for a polymorphic table using UUIDs.
     *
     * @param  string  $name
     * @param  string|null  $indexName
     * @return void
     */
    public function nullableUuidMorphs($name, $indexName = null)
    {
        $this->string("{$name}_type")->nullable();

        $this->uuid("{$name}_id")->nullable();

        $this->index(["{$name}_type", "{$name}_id"], $indexName);
    }

    /**
     * Add the proper columns for a polymorphic table using ULIDs.
     *
     * @param  string  $name
     * @param  string|null  $indexName
     * @return void
     */
    public function ulidMorphs($name, $indexName = null)
    {
        $this->string("{$name}_type");

        $this->ulid("{$name}_id");

        $this->index(["{$name}_type", "{$name}_id"], $indexName);
    }

    /**
     * Add nullable columns for a polymorphic table using ULIDs.
     *
     * @param  string  $name
     * @param  string|null  $indexName
     * @return void
     */
    public function nullableUlidMorphs($name, $indexName = null)
    {
        $this->string("{$name}_type")->nullable();

        $this->ulid("{$name}_id")->nullable();

        $this->index(["{$name}_type", "{$name}_id"], $indexName);
    }

    /**
     * Adds the `remember_token` column to the table.
     *
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function rememberToken()
    {
        return $this->string('remember_token', 100)->nullable();
    }

    /**
     * Add a comment to the table.
     *
     * @param  string  $comment
     * @return \Illuminate\Support\Fluent
     */
    public function comment($comment)
    {
        return $this->addCommand('tableComment', compact('comment'));
    }

    /**
     * Add a new index command to the blueprint.
     *
     * @param  string  $type
     * @param  string|array  $columns
     * @param  string  $index
     * @param  string|null  $algorithm
     * @return \Illuminate\Support\Fluent
     */
    protected function indexCommand($type, $columns, $index, $algorithm = null)
    {
        $columns = (array) $columns;

        // If no name was specified for this index, we will create one using a basic
        // convention of the table name, followed by the columns, followed by an
        // index type, such as primary or index, which makes the index unique.
        $index = $index ?: $this->createIndexName($type, $columns);

        return $this->addCommand(
            $type, compact('index', 'columns', 'algorithm')
        );
    }

    /**
     * Create a new drop index command on the blueprint.
     *
     * @param  string  $command
     * @param  string  $type
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    protected function dropIndexCommand($command, $type, $index)
    {
        $columns = [];

        // If the given "index" is actually an array of columns, the developer means
        // to drop an index merely by specifying the columns involved without the
        // conventional name, so we will build the index name from the columns.
        if (is_array($index)) {
            $index = $this->createIndexName($type, $columns = $index);
        }

        return $this->indexCommand($command, $columns, $index);
    }

    /**
     * Create a default index name for the table.
     *
     * @param  string  $type
     * @param  array  $columns
     * @return string
     */
    protected function createIndexName($type, array $columns)
    {
        $table = str_contains($this->table, '.')
            ? substr_replace($this->table, '.'.$this->prefix, strrpos($this->table, '.'), 1)
            : $this->prefix.$this->table;

        $index = strtolower($table.'_'.implode('_', $columns).'_'.$type);

        return str_replace(['-', '.'], '_', $index);
    }

    /**
     * Add a new column to the blueprint.
     *
     * @param  string  $type
     * @param  string  $name
     * @param  array  $parameters
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function addColumn($type, $name, array $parameters = [])
    {
        return $this->addColumnDefinition(new ColumnDefinition(
            array_merge(compact('type', 'name'), $parameters)
        ));
    }

    /**
     * Add a new column definition to the blueprint.
     *
     * @param  \Illuminate\Database\Schema\ColumnDefinition  $definition
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    protected function addColumnDefinition($definition)
    {
        $this->columns[] = $definition;

        if (! $this->creating()) {
            $this->commands[] = $definition;
        }

        if ($this->after) {
            $definition->after($this->after);

            $this->after = $definition->name;
        }

        return $definition;
    }

    /**
     * Add the columns from the callback after the given column.
     *
     * @param  string  $column
     * @param  \Closure  $callback
     * @return void
     */
    public function after($column, Closure $callback)
    {
        $this->after = $column;

        $callback($this);

        $this->after = null;
    }

    /**
     * Remove a column from the schema blueprint.
     *
     * @param  string  $name
     * @return $this
     */
    public function removeColumn($name)
    {
        $this->columns = array_values(array_filter($this->columns, function ($c) use ($name) {
            return $c['name'] != $name;
        }));

        $this->commands = array_values(array_filter($this->commands, function ($c) use ($name) {
            return ! $c instanceof ColumnDefinition || $c['name'] != $name;
        }));

        return $this;
    }

    /**
     * Add a new command to the blueprint.
     *
     * @param  string  $name
     * @param  array  $parameters
     * @return \Illuminate\Support\Fluent
     */
    protected function addCommand($name, array $parameters = [])
    {
        $this->commands[] = $command = $this->createCommand($name, $parameters);

        return $command;
    }

    /**
     * Create a new Fluent command.
     *
     * @param  string  $name
     * @param  array  $parameters
     * @return \Illuminate\Support\Fluent
     */
    protected function createCommand($name, array $parameters = [])
    {
        return new Fluent(array_merge(compact('name'), $parameters));
    }

    /**
     * Get the table the blueprint describes.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the table prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Get the columns on the blueprint.
     *
     * @return \Illuminate\Database\Schema\ColumnDefinition[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Get the commands on the blueprint.
     *
     * @return \Illuminate\Support\Fluent[]
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * Determine if the blueprint has state.
     *
     * @param  mixed  $name
     * @return bool
     */
    private function hasState(): bool
    {
        return ! is_null($this->state);
    }

    /**
     * Get the state of the blueprint.
     *
     * @return \Illuminate\Database\Schema\BlueprintState
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Get the columns on the blueprint that should be added.
     *
     * @return \Illuminate\Database\Schema\ColumnDefinition[]
     */
    public function getAddedColumns()
    {
        return array_filter($this->columns, function ($column) {
            return ! $column->change;
        });
    }

    /**
     * Get the columns on the blueprint that should be changed.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @return \Illuminate\Database\Schema\ColumnDefinition[]
     */
    public function getChangedColumns()
    {
        return array_filter($this->columns, function ($column) {
            return (bool) $column->change;
        });
    }
}
