<?php

namespace Illuminate\Database\Schema;

use Illuminate\Support\Fluent;

/**
 * @method ForeignKeyDefinition deferrable(bool $value = true) Set the foreign key as deferrable (PostgreSQL)
 * @method ForeignKeyDefinition initiallyImmediate(bool $value = true) Set the default time to check the constraint (PostgreSQL)
 * @method ForeignKeyDefinition on(string $table) Specify the referenced table
 * @method ForeignKeyDefinition onDelete(string $action) Specify the "on delete" action
 * @method ForeignKeyDefinition onUpdate(string $action) Specify the "on update" action
 * @method ForeignKeyDefinition references(string|array $columns) Specify the referenced column(s)
 */
class ForeignKeyDefinition extends Fluent
{
    //
}
