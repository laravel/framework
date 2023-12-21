<?php

namespace Illuminate\Database\Schema;

/**
 * @method $this onlyWhenEnforced() Add a primary index
 */
class IdColumnDefinition extends ColumnDefinition
{
    /**
     * Set the column to be added if enforced.
     *
     * @return $this
     */
    public function onlyWhenEnforced()
    {
        if (Builder::$enforceIncrementalPrimaryKey === false) {
            $this->shouldBeSkipped = true;
        }

        return $this;
    }
}
