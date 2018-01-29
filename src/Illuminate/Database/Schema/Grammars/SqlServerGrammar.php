<?php

namespace Illuminate\Database\Schema\Grammars;

use Illuminate\Database\Schema\Columns\VariableLength;
use Illuminate\Database\Schema\Columns\Column;
use Illuminate\Database\Schema\Columns\Decimal;
use Illuminate\Database\Schema\Columns\Enum;
use Illuminate\Database\Schema\Columns\Integer;
use Illuminate\Database\Schema\Columns\Text;
use Illuminate\Database\Schema\Columns\Time;
use Illuminate\Database\Schema\Columns\Timestamp;
use Illuminate\Support\Fluent;
use Illuminate\Database\Schema\Blueprint;

class SqlServerGrammar extends Grammar
{
    /**
     * If this Grammar supports schema changes wrapped in a transaction.
     *
     * @var bool
     */
    protected $transactions = true;

    /**
     * The possible column modifiers.
     *
     * @var array
     */
    protected $modifiers = ['Increment', 'Collate', 'Nullable', 'Default'];

    /**
     * The columns available as serials.
     *
     * @var array
     */
    protected $serials = ['tinyInteger', 'smallInteger', 'mediumInteger', 'integer', 'bigInteger'];

    /**
     * Compile the query to determine if a table exists.
     *
     * @return string
     */
    public function compileTableExists()
    {
        return "select * from sysobjects where type = 'U' and name = ?";
    }

    /**
     * Compile the query to determine the list of columns.
     *
     * @param  string  $table
     * @return string
     */
    public function compileColumnListing($table)
    {
        return "select col.name from sys.columns as col
                join sys.objects as obj on col.object_id = obj.object_id
                where obj.type = 'U' and obj.name = '$table'";
    }

    /**
     * Compile a create table command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileCreate(Blueprint $blueprint, Fluent $command)
    {
        $columns = implode(', ', $this->getColumns($blueprint));

        return 'create table '.$this->wrapTable($blueprint)." ($columns)";
    }

    /**
     * Compile a column addition table command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileAdd(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('alter table %s add %s',
            $this->wrapTable($blueprint),
            implode(', ', $this->getColumns($blueprint))
        );
    }

    /**
     * Compile a primary key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compilePrimary(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('alter table %s add constraint %s primary key (%s)',
            $this->wrapTable($blueprint),
            $this->wrap($command->index),
            $this->columnize($command->columns)
        );
    }

    /**
     * Compile a unique key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileUnique(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('create unique index %s on %s (%s)',
            $this->wrap($command->index),
            $this->wrapTable($blueprint),
            $this->columnize($command->columns)
        );
    }

    /**
     * Compile a plain index key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileIndex(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('create index %s on %s (%s)',
            $this->wrap($command->index),
            $this->wrapTable($blueprint),
            $this->columnize($command->columns)
        );
    }

    /**
     * Compile a spatial index key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileSpatialIndex(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('create spatial index %s on %s (%s)',
            $this->wrap($command->index),
            $this->wrapTable($blueprint),
            $this->columnize($command->columns)
        );
    }

    /**
     * Compile a drop table command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDrop(Blueprint $blueprint, Fluent $command)
    {
        return 'drop table '.$this->wrapTable($blueprint);
    }

    /**
     * Compile a drop table (if exists) command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropIfExists(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('if exists (select * from INFORMATION_SCHEMA.TABLES where TABLE_NAME = %s) drop table %s',
            "'".str_replace("'", "''", $this->getTablePrefix().$blueprint->getTable())."'",
            $this->wrapTable($blueprint)
        );
    }

    /**
     * Compile the SQL needed to drop all tables.
     *
     * @return string
     */
    public function compileDropAllTables()
    {
        return "EXEC sp_msforeachtable 'DROP TABLE ?'";
    }

    /**
     * Compile a drop column command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropColumn(Blueprint $blueprint, Fluent $command)
    {
        $columns = $this->wrapArray($command->columns);

        return 'alter table '.$this->wrapTable($blueprint).' drop column '.implode(', ', $columns);
    }

    /**
     * Compile a drop primary key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropPrimary(Blueprint $blueprint, Fluent $command)
    {
        $index = $this->wrap($command->index);

        return "alter table {$this->wrapTable($blueprint)} drop constraint {$index}";
    }

    /**
     * Compile a drop unique key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropUnique(Blueprint $blueprint, Fluent $command)
    {
        $index = $this->wrap($command->index);

        return "drop index {$index} on {$this->wrapTable($blueprint)}";
    }

    /**
     * Compile a drop index command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropIndex(Blueprint $blueprint, Fluent $command)
    {
        $index = $this->wrap($command->index);

        return "drop index {$index} on {$this->wrapTable($blueprint)}";
    }

    /**
     * Compile a drop spatial index command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropSpatialIndex(Blueprint $blueprint, Fluent $command)
    {
        return $this->compileDropIndex($blueprint, $command);
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
        $index = $this->wrap($command->index);

        return "alter table {$this->wrapTable($blueprint)} drop constraint {$index}";
    }

    /**
     * Compile a rename table command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileRename(Blueprint $blueprint, Fluent $command)
    {
        $from = $this->wrapTable($blueprint);

        return "sp_rename {$from}, ".$this->wrapTable($command->to);
    }

    /**
     * Compile the command to enable foreign key constraints.
     *
     * @return string
     */
    public function compileEnableForeignKeyConstraints()
    {
        return 'EXEC sp_msforeachtable @command1="print \'?\'", @command2="ALTER TABLE ? WITH CHECK CHECK CONSTRAINT all";';
    }

    /**
     * Compile the command to disable foreign key constraints.
     *
     * @return string
     */
    public function compileDisableForeignKeyConstraints()
    {
        return 'EXEC sp_msforeachtable "ALTER TABLE ? NOCHECK CONSTRAINT all";';
    }

    /**
     * Create the column definition for a char type.
     *
     * @param  \Illuminate\Database\Schema\Columns\VariableLength  $column
     * @return string
     */
    protected function typeChar(VariableLength $column)
    {
        return "nchar({$column->length})";
    }

    /**
     * Create the column definition for a string type.
     *
     * @param  \Illuminate\Database\Schema\Columns\VariableLength  $column
     * @return string
     */
    protected function typeString(VariableLength $column)
    {
        return "nvarchar({$column->length})";
    }

    /**
     * Create the column definition for a text type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Text  $column
     * @return string
     */
    protected function typeText(Text $column)
    {
        return 'nvarchar(max)';
    }

    /**
     * Create the column definition for a medium text type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Text  $column
     * @return string
     */
    protected function typeMediumText(Text $column)
    {
        return 'nvarchar(max)';
    }

    /**
     * Create the column definition for a long text type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Text  $column
     * @return string
     */
    protected function typeLongText(Text $column)
    {
        return 'nvarchar(max)';
    }

    /**
     * Create the column definition for an integer type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Integer  $column
     * @return string
     */
    protected function typeInteger(Integer $column)
    {
        return 'int';
    }

    /**
     * Create the column definition for a big integer type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Integer  $column
     * @return string
     */
    protected function typeBigInteger(Integer $column)
    {
        return 'bigint';
    }

    /**
     * Create the column definition for a medium integer type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Integer  $column
     * @return string
     */
    protected function typeMediumInteger(Integer $column)
    {
        return 'int';
    }

    /**
     * Create the column definition for a tiny integer type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Integer  $column
     * @return string
     */
    protected function typeTinyInteger(Integer $column)
    {
        return 'tinyint';
    }

    /**
     * Create the column definition for a small integer type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Integer  $column
     * @return string
     */
    protected function typeSmallInteger(Integer $column)
    {
        return 'smallint';
    }

    /**
     * Create the column definition for a float type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Decimal  $column
     * @return string
     */
    protected function typeFloat(Decimal $column)
    {
        return 'float';
    }

    /**
     * Create the column definition for a double type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Decimal  $column
     * @return string
     */
    protected function typeDouble(Decimal $column)
    {
        return 'float';
    }

    /**
     * Create the column definition for a decimal type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Decimal  $column
     * @return string
     */
    protected function typeDecimal(Decimal $column)
    {
        return "decimal({$column->total}, {$column->places})";
    }

    /**
     * Create the column definition for a boolean type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string
     */
    protected function typeBoolean(Column $column)
    {
        return 'bit';
    }

    /**
     * Create the column definition for an enumeration type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Enum  $column
     * @return string
     */
    protected function typeEnum(Enum $column)
    {
        return sprintf(
            'nvarchar(255) check ("%s" in (%s))',
            $column->name,
            $this->quoteString($column->allowed)
        );
    }

    /**
     * Create the column definition for a json type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string
     */
    protected function typeJson(Column $column)
    {
        return 'nvarchar(max)';
    }

    /**
     * Create the column definition for a jsonb type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string
     */
    protected function typeJsonb(Column $column)
    {
        return 'nvarchar(max)';
    }

    /**
     * Create the column definition for a date type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string
     */
    protected function typeDate(Column $column)
    {
        return 'date';
    }

    /**
     * Create the column definition for a date-time type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Time  $column
     * @return string
     */
    protected function typeDateTime(Time $column)
    {
        return $column->precision ? "datetime2($column->precision)" : 'datetime';
    }

    /**
     * Create the column definition for a date-time (with time zone) type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Time  $column
     * @return string
     */
    protected function typeDateTimeTz(Time $column)
    {
        return $column->precision ? "datetimeoffset($column->precision)" : 'datetimeoffset';
    }

    /**
     * Create the column definition for a time type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Time  $column
     * @return string
     */
    protected function typeTime(Time $column)
    {
        return $column->precision ? "time($column->precision)" : 'time';
    }

    /**
     * Create the column definition for a time (with time zone) type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Time  $column
     * @return string
     */
    protected function typeTimeTz(Time $column)
    {
        return $this->typeTime($column);
    }

    /**
     * Create the column definition for a timestamp type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Timestamp  $column
     * @return string
     */
    protected function typeTimestamp(Timestamp $column)
    {
        $columnType = $column->precision ? "datetime2($column->precision)" : 'datetime';

        return $column->useCurrent ? "$columnType default CURRENT_TIMESTAMP" : $columnType;
    }

    /**
     * Create the column definition for a timestamp (with time zone) type.
     *
     * @link https://msdn.microsoft.com/en-us/library/bb630289(v=sql.120).aspx
     *
     * @param  \Illuminate\Database\Schema\Columns\Timestamp  $column
     * @return string
     */
    protected function typeTimestampTz(Timestamp $column)
    {
        if ($column->useCurrent) {
            $columnType = $column->precision ? "datetimeoffset($column->precision)" : 'datetimeoffset';

            return "$columnType default CURRENT_TIMESTAMP";
        }

        return "datetimeoffset($column->precision)";
    }

    /**
     * Create the column definition for a year type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string
     */
    protected function typeYear(Column $column)
    {
        return 'int';
    }

    /**
     * Create the column definition for a binary type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string
     */
    protected function typeBinary(Column $column)
    {
        return 'varbinary(max)';
    }

    /**
     * Create the column definition for a uuid type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string
     */
    protected function typeUuid(Column $column)
    {
        return 'uniqueidentifier';
    }

    /**
     * Create the column definition for an IP address type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string
     */
    protected function typeIpAddress(Column $column)
    {
        return 'nvarchar(45)';
    }

    /**
     * Create the column definition for a MAC address type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string
     */
    protected function typeMacAddress(Column $column)
    {
        return 'nvarchar(17)';
    }

    /**
     * Create the column definition for a spatial Geometry type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string
     */
    public function typeGeometry(Column $column)
    {
        return 'geography';
    }

    /**
     * Create the column definition for a spatial Point type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string
     */
    public function typePoint(Column $column)
    {
        return 'geography';
    }

    /**
     * Create the column definition for a spatial LineString type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string
     */
    public function typeLineString(Column $column)
    {
        return 'geography';
    }

    /**
     * Create the column definition for a spatial Polygon type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string
     */
    public function typePolygon(Column $column)
    {
        return 'geography';
    }

    /**
     * Create the column definition for a spatial GeometryCollection type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string
     */
    public function typeGeometryCollection(Column $column)
    {
        return 'geography';
    }

    /**
     * Create the column definition for a spatial MultiPoint type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string
     */
    public function typeMultiPoint(Column $column)
    {
        return 'geography';
    }

    /**
     * Create the column definition for a spatial MultiLineString type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string
     */
    public function typeMultiLineString(Column $column)
    {
        return 'geography';
    }

    /**
     * Create the column definition for a spatial MultiPolygon type.
     *
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string
     */
    public function typeMultiPolygon(Column $column)
    {
        return 'geography';
    }

    /**
     * Get the SQL for a collation column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string|null
     */
    protected function modifyCollate(Blueprint $blueprint, Column $column)
    {
        if ($column instanceof Text && ! is_null($column->collation)) {
            return ' collate '.$column->collation;
        }
    }

    /**
     * Get the SQL for a nullable column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string|null
     */
    protected function modifyNullable(Blueprint $blueprint, Column $column)
    {
        return $column->nullable ? ' null' : ' not null';
    }

    /**
     * Get the SQL for a default column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string|null
     */
    protected function modifyDefault(Blueprint $blueprint, Column $column)
    {
        if (! is_null($column->default)) {
            return ' default '.$this->getDefaultValue($column->default);
        }
    }

    /**
     * Get the SQL for an auto-increment column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Database\Schema\Columns\Column  $column
     * @return string|null
     */
    protected function modifyIncrement(Blueprint $blueprint, Column $column)
    {
        if ($this->isColumnSerial($column) && $column->autoIncrement) {
            return ' identity primary key';
        }
    }

    /**
     * Wrap a table in keyword identifiers.
     *
     * @param  \Illuminate\Database\Query\Expression|string  $table
     * @return string
     */
    public function wrapTable($table)
    {
        if ($table instanceof Blueprint && $table->temporary) {
            $this->setTablePrefix('#');
        }

        return parent::wrapTable($table);
    }
}
