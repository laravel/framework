<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Attributes\PrimaryKey;
use ReflectionClass;

trait HasCustomizations
{
    /**
     * Apply customizations from attributes.
     *
     * @return void
     */
    protected function initializeHasCustomizations()
    {
        $primaryKeyAttribute = $this->resolveCustomPrimaryKey();
        if (! $primaryKeyAttribute) {
            return;
        }

        $this->setKeyName($primaryKeyAttribute->name);
        $this->setKeyType($primaryKeyAttribute->type);
        $this->setIncrementing($primaryKeyAttribute->incrementing);
    }

    /**
     * Resolve the custom primary key from the attributes.
     *
     * @return PrimaryKey|null
     */
    protected function resolveCustomPrimaryKey()
    {
        $reflectionClass = new ReflectionClass(static::class);
        $primaryKeyAttribute = $reflectionClass->getAttributes(PrimaryKey::class);

        return $primaryKeyAttribute === []
            ? null
            : $primaryKeyAttribute[0]->newInstance();
    }
}
