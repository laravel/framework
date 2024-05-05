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

        if (! is_null($primaryKeyAttribute->name)) {
            $this->setKeyName($primaryKeyAttribute->name);
        }
        if (! is_null($primaryKeyAttribute->type)) {
            $this->setKeyType($primaryKeyAttribute->type);
        }
        if (! is_null($primaryKeyAttribute->incrementing)) {
            $this->setIncrementing($primaryKeyAttribute->incrementing);
        }
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
