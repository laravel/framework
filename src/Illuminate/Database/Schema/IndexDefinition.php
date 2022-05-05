<?php

namespace Illuminate\Database\Schema;

use Illuminate\Support\Fluent;

/**
 * @method $this algorithm(string $algorithm) Specify an algorithm for the index (MySQL/PostgreSQL)
 * @method $this language(string $language) Specify a language for the full text index (PostgreSQL)
 */
class IndexDefinition extends Fluent
{
    //
}
