<?php

declare(strict_types=1);

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Str;

trait JoinsModels
{
    /**
     * @param class-string<Model>|Model|Builder<Model> $model
     * @param string $joinType
     * @param string|null $overrideJoinColumnName
     * @return static
     */
    public function joinMany($model, string $joinType = 'inner', ?string $overrideJoinColumnName = null): static  {
        $modelToJoin = is_string($model) ? new $model() : $model;
        if($model instanceof Builder){
            $scopes = $model->getScopes();
            $modelToJoin = $model->getModel();
        } else {
            $scopes = $modelToJoin->getGlobalScopes();
        }

        $this->joinManyOn($this->getModel(), $modelToJoin, $joinType,null, $overrideJoinColumnName);
        $this->applyScopesWith($scopes, $modelToJoin);

        return $this;
    }

    /**
     * @param class-string|Model $model
     * @param string $joinType
     * @param string|null $overrideBaseColumn
     * @return static
     */
    public function joinOne($model, string $joinType = 'inner', ?string $overrideBaseColumn = null): static {
        $modelToJoin = is_string($model) ? new $model() : $model;
        if($model instanceof Builder){
            $scopes = $model->getScopes();
            $modelToJoin = $model->getModel();
        } else {
            $scopes = $modelToJoin->getGlobalScopes();
        }
        $this->joinOneOn($this->getModel(), $modelToJoin, $joinType, $overrideBaseColumn);
        $this->applyScopesWith($scopes, $modelToJoin);

        return $this;
    }


    private function joinManyOn(Model $baseModel, Model $modelToJoin, ?string $joinType = 'inner', ?string $overrideBaseColumnName = null, ?string $overrideJoinColumnName = null): static
    {
        $manyJoinColumnName = $overrideJoinColumnName ?? (Str::singular($baseModel->getTable()). '_' . $baseModel->getKeyName());
        return $this->join(
            $modelToJoin->getTable(),
            $modelToJoin->qualifyColumn($manyJoinColumnName),
            '=',
            $baseModel->qualifyColumn($overrideBaseColumnName ?? $baseModel->getKeyName()),
            $joinType
        );
    }

    private function joinOneOn(Model $baseModel, Model $modelToJoin, string $joinType = 'inner', string $overrideBaseColumnName = null, string $overrideJoinColumnName = null): static
    {
        $manyJoinColumnName = $overrideBaseColumnName ?? (Str::singular($modelToJoin->getTable()). '_' . $modelToJoin->getKeyName());
        return $this->join(
            $modelToJoin->getTable(),
            $modelToJoin->qualifyColumn($overrideJoinColumnName ?? $modelToJoin->getKeyName()),
            '=',
            $baseModel->qualifyColumn($manyJoinColumnName),
            $joinType
        );
    }

    /**
     * @param Scope $scopes
     * @param Model $model
     * @return static
     */
    private function applyScopesWith(array $scopes, Model $model): static
    {
        foreach($scopes as $scope){
            $scope->apply($this, $model);
        }
        return $this;
    }
}
