<?php

namespace Illuminate\Refine;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class Refiner
{
    /**
     * Return the keys to use to refine the query.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string[]
     */
    public function keys(Request $request): array
    {
        return $request->keys();
    }

    /**
     * Run before the refiner executes its matched methods.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function before(Builder $query, Request $request): void
    {
        //
    }

    /**
     * Run after the refiner has executed all its matched methods.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function after(Builder $query, Request $request): void
    {
        //
    }
}
