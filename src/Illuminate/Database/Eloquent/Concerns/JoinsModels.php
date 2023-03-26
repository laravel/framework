<?php

declare(strict_types=1);

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Query\JoinClause;
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
        /** @var Builder $builder */
        $builder = match(true) {
            is_string($model) => (new $model())->newQuery(),
            $model instanceof Builder => $model,
            $model instanceof Model => $model->newQuery(),
            $model instanceof Relation => $model->getQuery(),
        };

        return $this->joinManyOn($this->getModel(), $builder, $joinType,null, $overrideJoinColumnName);
    }

    /**
     * @param class-string|Model $model
     * @param string $joinType
     * @param string|null $overrideBaseColumn
     * @return static
     */
    public function joinOne($model, string $joinType = 'inner', ?string $overrideBaseColumn = null): static {
        $builder = match(true) {
            is_string($model) => (new $model())->newQuery(),
            $model instanceof Builder => $model,
            $model instanceof Model => $model->newQuery(),
            $model instanceof Relation => $model->getQuery(),
        };

        $this->joinOneOn($this->getModel(), $builder, $joinType, $overrideBaseColumn);

        return $this;
    }


    private function joinManyOn(Model $baseModel, Builder $builderToJoin, ?string $joinType = 'inner', ?string $overrideBaseColumnName = null, ?string $overrideJoinColumnName = null): static
    {
        $modelToJoin = $builderToJoin->getModel();
        $manyJoinColumnName = $overrideJoinColumnName ?? (Str::singular($baseModel->getTable()). '_' . $baseModel->getKeyName());
        $baseColumnName = $overrideBaseColumnName ?? $baseModel->getKeyName();
        $this->join(
            $modelToJoin->getTable(), fn(JoinClause $join) =>
                $join->on(
                    $modelToJoin->qualifyColumn($manyJoinColumnName),
                    '=',
                    $baseModel->qualifyColumn($baseColumnName),
                )->addNestedWhereQuery($builderToJoin->applyScopes()->getQuery()),
            type: $joinType
        );

        return $this;
    }

    private function joinOneOn(Model $baseModel, Builder $builderToJoin, string $joinType = 'inner', string $overrideBaseColumnName = null, string $overrideJoinColumnName = null): static
    {
        $modelToJoin = $builderToJoin->getModel();
        $joinColumnName = $overrideBaseColumnName ?? $modelToJoin->getKeyName();
        $baseColumnName = $overrideJoinColumnName ?? (Str::singular($baseModel->getTable()). '_' . $baseModel->getKeyName());
        $this->join(
            $modelToJoin->getTable(), fn(JoinClause $join) =>
                $join->on(
                    $modelToJoin->qualifyColumn($joinColumnName),
                    '=',
                    $baseModel->qualifyColumn($baseColumnName),
                )->addNestedWhereQuery($builderToJoin->getQuery()),
            type: $joinType
        );
        $this->applyScopesWith($builderToJoin->getScopes(), $modelToJoin);
        return $this;
    }

    /**
     * @param Scope[] $scopes
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
