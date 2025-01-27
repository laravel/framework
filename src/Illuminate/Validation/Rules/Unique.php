<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Conditionable;
use Stringable;

class Unique implements Stringable
{
    use Conditionable, DatabaseRule;

    /**
     * The ID that should be ignored.
     *
     * @var mixed
     */
    protected $ignore;

    /**
     * The name of the ID column.
     *
     * @var string
     */
    protected $idColumn = 'id';

    /**
     * Ignore the given ID during the unique check.
     *
     * @param  mixed  $id
     * @param  string|null  $idColumn
     * @return $this
     */
    public function ignore($id, $idColumn = null)
    {
        if ($id instanceof Model) {
            return $this->ignoreModel($id, $idColumn);
        }

        $this->ignore = $id;
        $this->idColumn = $idColumn ?? 'id';

        return $this;
    }

    /**
     * Ignore the given model during the unique check.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string|null  $idColumn
     * @return $this
     */
    public function ignoreModel($model, $idColumn = null)
    {
        $this->idColumn = $idColumn ?? $model->getKeyName();
        $this->ignore = $model->{$this->idColumn};

        return $this;
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        return rtrim(sprintf('unique:%s,%s,%s,%s,%s',
            $this->table,
            $this->column,
            $this->ignore ? '"'.addslashes($this->ignore).'"' : 'NULL',
            $this->idColumn,
            $this->formatWheres()
        ), ',');
    }

    /**
     * Apply a condition to exclude soft-deleted records.
     *
     * @param Model|null $model
     * @return $this
     */
    public function withSoftDeletes(?Model $model = null): static
    {
        $this->where(function ($query) use ($model) {
            $deletedAtColumn = $model && method_exists($model, 'getDeletedAtColumn')
                ? $model->getDeletedAtColumn()
                : 'deleted_at';

            $query->whereNull($deletedAtColumn);
        });

        return $this;
    }
}
