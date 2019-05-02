<?php

namespace Illuminate\Database\Schema;

use Illuminate\Support\Fluent;

/**
 * @method ForeignKeyDefinition references(string $table) Specify the referenced table
 * @method ForeignKeyDefinition on(string $column) Specify the referenced column
 * @method ForeignKeyDefinition onDelete(string $action) Add an ON DELETE action
 * @method ForeignKeyDefinition onUpdate(string $action) Add an ON UPDATE action
 */
class ForeignKeyDefinition extends Fluent
{
    //
}
