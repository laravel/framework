<?php

namespace Illuminate\Database\Schema;

use Illuminate\Support\Fluent;

/**
 * @method $this algorithm(string $algorithm) Specify an algorithm for the index (MySQL/PostgreSQL)
 * @method $this language(string $language) Specify a language for the full text index (PostgreSQL)
 * @method $this deferrable(bool $value = true) Specify that the unique index is deferrable (PostgreSQL)
 * @method $this initiallyImmediate(bool $value = true) Specify the default time to check the unique index constraint (PostgreSQL)
 */
class IndexDefinition extends Fluent
{
    //
}
