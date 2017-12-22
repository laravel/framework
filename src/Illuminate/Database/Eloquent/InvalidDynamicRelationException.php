<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class InvalidDynamicRelationException extends RuntimeException
{
    public function __construct($modelClass, $relationName)
    {
        parent::__construct("Relation '$relationName' is invalid for model '$modelClass'");
    }
}
