<?php

namespace Illuminate\Database\Schema;

use Illuminate\Support\Fluent;

/**
 * @method $this algorithm(string $algorithm) Specify an algorithm for the index (MySQL/PostgreSQL)
 * @method $this language(string $language) Specify a language for the full text index (PostgreSQL)
 * @method $this deferrable(bool $value = true) Set the unique index as deferrable (PostgreSQL)
 * @method $this initiallyImmediate(bool $value = true) Set the default time to check the constraint (PostgreSQL)
 */
class IndexDefinition extends Fluent
{
    //
}
