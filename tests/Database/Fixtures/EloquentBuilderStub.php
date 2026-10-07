<?php

namespace Illuminate\Tests\Database\Fixtures;

use Illuminate\Database\Eloquent\Builder;

/**
 * Declares the query builder methods that Builder otherwise only forwards through __call(), so they can be doubled.
 */
class EloquentBuilderStub extends Builder
{
    public function __construct()
    {
        //
    }

    public function whereIntegerInRaw(...$arguments)
    {
        //
    }

    public function join(...$arguments)
    {
        //
    }

    public function whereNotNull(...$arguments)
    {
        //
    }

    public function whereIn(...$arguments)
    {
        //
    }

    public function lockForUpdate(...$arguments)
    {
        //
    }

    public function useWritePdo(...$arguments)
    {
        //
    }

    public function withTrashed(...$arguments)
    {
        //
    }

    public function withoutTrashed(...$arguments)
    {
        //
    }

    public function onlyTrashed(...$arguments)
    {
        //
    }

    public function whereNull(...$arguments)
    {
        //
    }

    public function orderBy(...$arguments)
    {
        //
    }

    public function limit(...$arguments)
    {
        //
    }
}
