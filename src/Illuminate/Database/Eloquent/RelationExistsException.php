<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class RelationExistsException extends RuntimeException
{
    public function __construct($modelClass, $relationName)
    {
        parent::__construct("Relation '$relationName' exists in model '$modelClass'");
    }
}
