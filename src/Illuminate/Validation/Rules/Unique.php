<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class Unique
{
    use DatabaseRule;

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
     * Indicates that the soft deleted records must be checked even if the model implements soft delete.
     *
     * @var bool
     */
    protected $checkSoftDelete = false;

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
     * Checks for soft deleted records even if the model implements soft delete.
     * @return $this
     */
    public function checkSoftDelete()
    {
        $this->checkSoftDelete = true;

        return $this;
    }

    /**
     * Eliminate the need to exclude soft delete records.
     * @return $this
     */
    protected function ignoreSoftDelete()
    {
        if (is_null($this->model)) {
            return $this;
        }

        if (! $this->model->hasGlobalScope(SoftDeletingScope::class)) {
            return $this;
        }

        if (collect($this->wheres)->firstWhere('column', $this->model->getDeletedAtColumn())) {
            return $this;
        }

        if ($this->checkSoftDelete) {
            return $this;
        }

        $this->whereNull($this->model->getDeletedAtColumn());

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
            $this->ignoreSoftDelete()->formatWheres()
        ), ',');
    }
}
