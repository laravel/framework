<?php

namespace Illuminate\Database\Schema;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Fluent;

/**
 * @method $this after(string $column) Place the column "after" another column (MySQL)
 * @method $this always() Used as a modifier for generatedAs() (PostgreSQL)
 * @method $this autoIncrement() Set INTEGER columns as auto-increment (primary key)
 * @method $this change() Change the column
 * @method $this charset(string $charset) Specify a character set for the column (MySQL)
 * @method $this collation(string $collation) Specify a collation for the column (MySQL/PostgreSQL/SQL Server)
 * @method $this comment(string $comment) Add a comment to the column (MySQL)
 * @method $this default(mixed $value) Specify a "default" value for the column
 * @method $this first() Place the column "first" in the table (MySQL)
 * @method $this generatedAs(string|Expression $expression = null) Create a SQL compliant identity column (PostgreSQL)
 * @method $this index(string $indexName = null) Add an index
 * @method $this nullable(bool $value = true) Allow NULL values to be inserted into the column
 * @method $this persisted() Mark the computed generated column as persistent (SQL Server)
 * @method $this primary() Add a primary index
 * @method $this spatialIndex() Add a spatial index
 * @method $this storedAs(string $expression) Create a stored generated column (MySQL)
 * @method $this unique(string $indexName = null) Add a unique index
 * @method $this unsigned() Set the INTEGER column as UNSIGNED (MySQL)
 * @method $this useCurrent() Set the TIMESTAMP column to use CURRENT_TIMESTAMP as default value
 * @method $this virtualAs(string $expression) Create a virtual generated column (MySQL)
 */
class ColumnDefinition extends Fluent
{
    //
}
