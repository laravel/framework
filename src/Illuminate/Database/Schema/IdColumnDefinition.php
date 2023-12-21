<?php

namespace Illuminate\Database\Schema;

/**
 * @method $this onlyWhenEnforced() Add a primary index
 */
class IdColumnDefinition extends ColumnDefinition
{
    public $shouldBeSkipped = false;

    public function onlyWhenEnforced()
    {
        if (Builder::$enforceIncrementalPrimaryKey === false) {
            $this->shouldBeSkipped = true;
        }

        return $this;
    }
}
