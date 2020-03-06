<?php

namespace Illuminate\Database\Schema;

use Illuminate\Support\Str;

class ForeignIdColumnDefinition extends ColumnDefinition
{
    /**
     * The schema builder blueprint instance.
     *
     * @var \Illuminate\Database\Schema\Blueprint
     */
    protected $blueprint;

    /**
     * Create a new foreign ID column definition.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  array  $attributes
     * @return void
     */
    public function __construct(Blueprint $blueprint, $attributes = [])
    {
        parent::__construct($attributes);

        $this->blueprint = $blueprint;
    }

    /**
     * Create a foreign key constraint on this column referencing the "id" column of the conventionally related table.
     *
     * @param  string $foreignTable
     * @return \Illuminate\Support\Fluent|\Illuminate\Database\Schema\ForeignKeyDefinition
     */
    public function constrained(string $foreignTable = '')
    {
        if ($foreignTable === '') {
            return $this->references('id')->on(Str::plural(Str::before($this->name, '_id')));
        }
        
        return $this->references('id')->on($foreignTable);
    }

    /**
     * Specify which column this foreign ID references on another table.
     *
     * @return \Illuminate\Support\Fluent|\Illuminate\Database\Schema\ForeignKeyDefinition
     */
    public function references($column)
    {
        return $this->blueprint->foreign($this->name)->references($column);
    }
}
