<?php

namespace Illuminate\Database\Query;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class BuilderDebugger
{
    /**
     * @var Builder|EloquentBuilder
     */
    private $builder;

    /**
     * BuilderDebugger constructor.
     * @param Builder|EloquentBuilder $builder
     */
    public function __construct($builder)
    {
        $this->builder = $builder;
    }

    /**
     * Get compiled sql as string.
     *
     * @return string
     */
    public function getRawSql(): string
    {
        $bindings = $this->builder->getBindings();
        $sql = $this->builder->toSql();
        $pattern = '/\:\w+|\?/';

        foreach ($bindings as $index => $binding) {

            $replace = is_numeric($binding)
                ? $binding
                : sprintf("'%s'", str_replace("'", "\'", $binding));

            $sql = preg_replace($pattern, $replace, $sql, 1);
        }

        return (string) $sql;
    }

    /**
     * Die with dumping compiled sql.
     */
    public function rawSql(): void
    {
        dd($this->getRawSql());
    }

    /**
     * Dump compiled sql and return builder instance.
     *
     * @return Builder|EloquentBuilder
     */
    public function dumpRawSql()
    {
        dump($this->getRawSql());

        return $this->builder;
    }

    /**
     * Dump and die with count of results.
     *
     * @param string $columns
     */
    public function count(string $columns = '*'): void
    {
        dd($this->builder->count($columns));
    }

    /**
     * Dump count of elements and return builder instance.
     *
     * @param string $columns
     * @return EloquentBuilder|Builder
     */
    public function dumpCount(string $columns = '*')
    {
        dump($this->builder->count($columns));

        return $this->builder;
    }

    /**
     * Dump and die with the result of callback for builder instance.
     *
     * @param callable $callback
     */
    public function withCallback(callable $callback): void
    {
        dd($callback($this->builder));
    }

    /**
     * Dump callback result for builder and return builder instance.
     *
     * @param callable $callback
     * @return EloquentBuilder|Builder
     */
    public function dumpWithCallback(callable $callback)
    {
        dump($callback($this->builder));

        return $this->builder;
    }

    /**
     * Dump and die with clean sql.
     */
    public function toSql(): void
    {
        dd($this->builder->toSql());
    }

    /**
     * Dump clean sql and return builder instance.
     *
     * @return EloquentBuilder|Builder
     */
    public function dumpSql()
    {
        dump($this->builder->toSql());

        return $this->builder;
    }

    /**
     * Dump and die query bindings.
     */
    public function bindings(): void
    {
        dd($this->builder->getBindings());
    }

    /**
     * Dump query bindings and return builder instance.
     *
     * @return EloquentBuilder|Builder
     */
    public function dumpBindings()
    {
        dump($this->builder->getBindings());

        return $this->builder;
    }

    /**
     * Dump and die with builder instance.
     */
    public function instance(): void
    {
        dd($this->builder);
    }

    /**
     * Dump builder instance and return it.
     *
     * @return EloquentBuilder|Builder
     */
    public function dumpInstance()
    {
        dump($this->builder);

        return $this->builder;
    }
}
