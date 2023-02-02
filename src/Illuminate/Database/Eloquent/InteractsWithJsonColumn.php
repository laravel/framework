<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait InteractsWithJsonColumn
{
    protected array $dynamicMethods = [];

    /**
     * Boot the trait for the model.
     *
     * @return void
     */
    protected static function bootInteractsWithJsonColumn()
    {
        static::retrieved(function ($model) {
            $model->registerJsonColumnMethods();
        });
    }

    /**
     * Register dynamic methods for JSON columns.
     *
     * @return void
     */
    protected function registerJsonColumnMethods(): void
    {
        $table = $this->getTable();
        $columns = $this->getConnection()->getSchemaBuilder()->getColumnListing($table);

        foreach ($columns as $column) {
            if ($this->getConnection()->getSchemaBuilder()->getColumnType($table, $column) !== 'json') {
                continue;
            }

            $getter = 'get'.Str::studly($column);
            $setter = 'set'.Str::studly($column);

            $this->dynamicMethods[$getter] = function ($key = null, $default = null) use ($column) {
                $data = $this->asArray($column);
                if (! $key) {
                    return $data;
                }
                if (Str::contains($key, '->')) {
                    $key = str_replace('->', '.', $key);
                }

                return Arr::get($data, $key, $default);
            };

            $this->dynamicMethods[$setter] = function ($key, $value) use ($column) {
                if (Str::contains($key, '->')) {
                    return $this->update([$column.'->'.$key => $value]);
                }
                $data = $this->asArray($column);
                $data[$key] = $value;

                return $this->update([$column => $data]);
            };
        }
    }

    /**
     * Handle dynamic method calls into the method.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($method, $arguments): mixed
    {
        if (array_key_exists($method, $this->dynamicMethods)) {
            return call_user_func_array($this->dynamicMethods[$method], $arguments);
        }

        return parent::__call($method, $arguments);
    }

    /**
     * Convert the given value to an array.
     *
     * @param  string  $column
     * @return array
     */
    private function asArray($column)
    {
        $data = $this->$column;
        if ($data === null) {
            return [];
        }
        if (! is_array($data)) {
            $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        }

        return $data;
    }
}
