<?php

namespace Illuminate\Database\Schema\Grammars;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;

class MariaDbGrammar extends MySqlGrammar
{
    /** @inheritDoc */
    public function compileRenameColumn(Blueprint $blueprint, Fluent $command)
    {
        if (version_compare($this->connection->getServerVersion(), '10.5.2', '<')) {
            return $this->compileLegacyRenameColumn($blueprint, $command);
        }

        return parent::compileRenameColumn($blueprint, $command);
    }

    /**
     * Compile the query to determine the columns.
     *
     * @param  string|null  $schema
     * @param  string  $table
     * @return string
     */
    public function compileColumns($schema, $table)
    {
        if (version_compare($this->connection->getServerVersion(), '10.2.5', '<')) {
            return sprintf(
                'select column_name as `name`, data_type as `type_name`, column_type as `type`, '
                .'collation_name as `collation`, is_nullable as `nullable`, '
                .'column_default as `default`, column_comment as `comment`, '
                .'null as `expression`, extra as `extra` '
                .'from information_schema.columns where table_schema = %s and table_name = %s '
                .'order by ordinal_position asc',
                $schema ? $this->quoteString($schema) : 'schema()',
                $this->quoteString($table)
            );
        }

        return parent::compileColumns($schema, $table);
    }

    /**
     * Create the column definition for a uuid type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeUuid(Fluent $column)
    {
        if (version_compare($this->connection->getServerVersion(), '10.7.0', '<')) {
            return 'char(36)';
        }

        return 'uuid';
    }

    /**
     * Create the column definition for a spatial Geometry type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeGeometry(Fluent $column)
    {
        $subtype = $column->subtype ? strtolower($column->subtype) : null;

        if (! in_array($subtype, ['point', 'linestring', 'polygon', 'geometrycollection', 'multipoint', 'multilinestring', 'multipolygon'])) {
            $subtype = null;
        }

        return sprintf('%s%s',
            $subtype ?? 'geometry',
            $column->srid ? ' ref_system_id='.$column->srid : ''
        );
    }

    /**
     * Wrap the given JSON selector.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapJsonSelector($value)
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($value);

        return 'json_value('.$field.$path.')';
    }
}
