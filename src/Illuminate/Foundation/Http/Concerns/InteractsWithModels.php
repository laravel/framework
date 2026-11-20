<?php

namespace Illuminate\Foundation\Http\Concerns;

use Closure;
use Illuminate\Support\Str;

trait InteractsWithModels
{
    /**
     * Retrieve the model identified by the given input.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  string  $key
     * @param  class-string<TModel>|\Closure|null  $modelClass
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TModel>): mixed)|null  $callback
     * @return TModel|null
     */
    public function model($key, $modelClass = null, ?Closure $callback = null)
    {
        if ($this->isNotFilled($key)) {
            return null;
        }

        return $this->newModelQuery($key, $modelClass, $callback)
            ->whereKey($this->input($key))
            ->first();
    }

    /**
     * Retrieve the model identified by the given input or throw an exception.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  string  $key
     * @param  class-string<TModel>|\Closure|null  $modelClass
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TModel>): mixed)|null  $callback
     * @return TModel
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<TModel>
     */
    public function modelOrFail($key, $modelClass = null, ?Closure $callback = null)
    {
        $id = $this->isNotFilled($key) ? null : $this->input($key);

        return $this->newModelQuery($key, $modelClass, $callback)
            ->whereKey($id)
            ->firstOrFail();
    }

    /**
     * Retrieve the models identified by the given input.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  string  $key
     * @param  class-string<TModel>|\Closure|null  $modelClass
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TModel>): mixed)|null  $callback
     * @return \Illuminate\Database\Eloquent\Collection<int, TModel>
     */
    public function models($key, $modelClass = null, ?Closure $callback = null)
    {
        return $this->newModelQuery($key, $modelClass, $callback)
            ->findMany($this->array($key));
    }

    /**
     * Create a model query for the given input key.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  string  $key
     * @param  class-string<TModel>|\Closure|null  $modelClass
     * @param  (\Closure(\Illuminate\Database\Eloquent\Builder<TModel>): mixed)|null  $callback
     * @return \Illuminate\Database\Eloquent\Builder<TModel>
     */
    protected function newModelQuery($key, $modelClass = null, ?Closure $callback = null)
    {
        if ($modelClass instanceof Closure) {
            $callback = $modelClass;
            $modelClass = null;
        }

        $modelClass ??= $this->guessModelClass($key);

        $query = $modelClass::query();

        if ($callback) {
            $query->where($callback);
        }

        return $query;
    }

    /**
     * Guess the model class for the given input key.
     *
     * @param  string  $key
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected function guessModelClass($key)
    {
        $name = Str::studly(Str::singular(Str::chopEnd($key, ['_ids', '_id'])));

        $namespace = $this->container->getNamespace();

        return class_exists($namespace.'Models\\'.$name)
            ? $namespace.'Models\\'.$name
            : $namespace.$name;
    }
}
