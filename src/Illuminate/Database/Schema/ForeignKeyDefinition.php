<?php

namespace Illuminate\Database\Schema;

use Illuminate\Support\Fluent;

/**
 * @method ForeignKeyDefinition deferrable(bool $value = true) Set the foreign key as deferrable (PostgreSQL)
 * @method ForeignKeyDefinition initiallyImmediate(bool $value = true) Set the default time to check the constraint (PostgreSQL)
 * @method ForeignKeyDefinition on(string $table) Specify the referenced table
 * @method ForeignKeyDefinition onDelete(string $action) Add an ON DELETE action
 * @method ForeignKeyDefinition onUpdate(string $action) Add an ON UPDATE action
 * @method ForeignKeyDefinition references(string|array $columns) Specify the referenced column(s)
 */
class ForeignKeyDefinition extends Fluent
{
    /**
     * Indicate that updates should cascade.
     *
     * @return $this
     */
    public function cascadeOnUpdate()
    {
        return $this->onUpdate('cascade');
    }

    /**
     * Indicate that deletes should cascade.
     *
     * @return $this
     */
    public function cascadeOnDelete()
    {
        return $this->onDelete('cascade');
    }
}
