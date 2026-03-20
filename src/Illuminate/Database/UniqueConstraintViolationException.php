<?php

namespace Illuminate\Database;

class UniqueConstraintViolationException extends QueryException
{
    public array $columns = [];

    public ?string $index = null;

    public function setColumns(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    public function setIndex(?string $index): self
    {
        $this->index = $index;

        return $this;
    }
}
