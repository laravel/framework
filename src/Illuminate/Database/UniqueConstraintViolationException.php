<?php

namespace Illuminate\Database;

class UniqueConstraintViolationException extends QueryException
{
    /**
     * The unique index which prevented the query.
     *
     * @var string|null
     */
    public ?string $index = null;

    /**
     * The columns which caused the violation.
     *
     * @var list<string>
     */
    public array $columns = [];

    /**
     * Set the unique index which caused the violation.
     *
     * @param  string|null  $index
     * @return $this
     */
    public function setIndex(?string $index): self
    {
        $this->index = $index;

        return $this;
    }

    /**
     * Set the columns that caused the violation.
     *
     * @param  list<string>  $columns
     * @return $this
     */
    public function setColumns(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }
}
