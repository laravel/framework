<?php

namespace Illuminate\Database\Schema;

use Illuminate\Support\Fluent;
/**
 * @method ForeignDefinition references(string $column) The name of the column on the referenced table table.
 * @method ForeignDefinition on(string $table) The name of the table which is referenced.
 * @method ForeignDefinition onDelete(string $action) The desired action to execute when a foreign row is deleted.
 * @method ForeignDefinition onUpdate(string $action) The desired action to execute when a foreign row is updated.
*/
class ForeignDefinition extends Fluent
{
    //
}
