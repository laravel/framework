<?php

namespace Illuminate\Database\Schema;

use Illuminate\Support\Stringable;

class ForeignIdColumnDefinition extends ColumnDefinition
{
    /**
     * The schema builder blueprint instance.
     *
     * @var \Illuminate\Database\Schema\Blueprint
     */
    protected $blueprint;

    /**
     * Whether to skip automatic index creation.
     *
     * @var bool
     */
    protected $shouldSkipAutoIndex = false;

    /**
     * Create a new foreign ID column definition.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  array  $attributes
     */
    public function __construct(Blueprint $blueprint, $attributes = [])
    {
        parent::__construct($attributes);

        $this->blueprint = $blueprint;
    }

    /**
     * Indicate that the foreign key should not have an automatically created index.
     *
     * @return $this
     */
    public function withoutIndex()
    {
        $this->shouldSkipAutoIndex = true;

        return $this;
    }

    /**
     * Create a foreign key constraint on this column referencing the "id" column of the conventionally related table.
     *
     * @param  string|null  $table
     * @param  string|null  $column
     * @param  string|null  $indexName
     * @return \Illuminate\Database\Schema\ForeignKeyDefinition
     */
    public function constrained($table = null, $column = null, $indexName = null)
    {
        $table ??= $this->table;
        $column ??= $this->referencesModelColumn ?? 'id';

        $foreignKey = $this->references($column, $indexName)->on($table ?? (new Stringable($this->name))->beforeLast('_'.$column)->plural());

        if ($this->shouldSkipAutoIndex) {
            $foreignKey->withoutIndex();
        }

        return $foreignKey;
    }

    /**
     * Specify which column this foreign ID references on another table.
     *
     * @param  string  $column
     * @param  string  $indexName
     * @return \Illuminate\Database\Schema\ForeignKeyDefinition
     */
    public function references($column, $indexName = null)
    {
        $foreignKey = $this->blueprint->foreign($this->name, $indexName)->references($column);

        if ($this->shouldSkipAutoIndex) {
            $foreignKey->withoutIndex();
        }

        return $foreignKey;
    }
}
