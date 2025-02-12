<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Database\Eloquent\Builder;

trait Filterable
{

    protected $filterNamespace = 'App\Filters\\';

    public function scopeFilter(Builder $builder, array $params): void
    {
        if (is_array($params) and !empty($params)) {

            foreach ($params as $class => $methodOrValue) {

                $className = $this->filterNamespace . ucfirst($class);

                if (class_exists($className)) {

                    if (method_exists($className, $methodOrValue))
                        $className::{$methodOrValue}($builder);

                    if (method_exists($className, 'apply'))
                        $className::apply($builder, $methodOrValue);
                }
            }
        }
    }

    public function getFilterNamespace(): string
    {
        return $this->filterNamespace;
    }
}
