<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Constraints\CountInDatabase;
use Illuminate\Testing\Constraints\HasInDatabase;
use Illuminate\Testing\Constraints\SoftDeletedInDatabase;
use PHPUnit\Framework\Constraint\LogicalNot as ReverseConstraint;

trait InteractsWithDatabase
{
    /**
     * Assert that a given where condition exists in the database.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $table
     * @param  array  $data
     * @param  string|null  $connection
     * @return $this
     */
    protected function assertDatabaseHas($table, array $data, $connection = null)
    {
        $this->assertThat(
            $this->getTable($table), new HasInDatabase($this->getConnection($connection), $data)
        );

        return $this;
    }

    /**
     * Assert that a given where condition does not exist in the database.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $table
     * @param  array  $data
     * @param  string|null  $connection
     * @return $this
     */
    protected function assertDatabaseMissing($table, array $data, $connection = null)
    {
        $constraint = new ReverseConstraint(
            new HasInDatabase($this->getConnection($connection), $data)
        );

        $this->assertThat($this->getTable($table), $constraint);

        return $this;
    }

    /**
     * Assert the count of table entries.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $table
     * @param  int  $count
     * @param  string|null  $connection
     * @return $this
     */
    protected function assertDatabaseCount($table, int $count, $connection = null)
    {
        $this->assertThat(
            $this->getTable($table), new CountInDatabase($this->getConnection($connection), $count)
        );

        return $this;
    }

    /**
     * Assert the given record has been deleted.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $table
     * @param  array  $data
     * @param  string|null  $connection
     * @return $this
     */
    protected function assertDeleted($table, array $data = [], $connection = null)
    {
        if ($table instanceof Model) {
            return $this->assertDatabaseMissing($table->getTable(), [$table->getKeyName() => $table->getKey()], $table->getConnectionName());
        }

        $this->assertDatabaseMissing($this->getTable($table), $data, $connection);

        return $this;
    }

    /**
     * Assert the given record has been "soft deleted".
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $table
     * @param  array  $data
     * @param  string|null  $connection
     * @param  string|null  $deletedAtColumn
     * @return $this
     */
    protected function assertSoftDeleted($table, array $data = [], $connection = null, $deletedAtColumn = 'deleted_at')
    {
        if ($this->isSoftDeletableModel($table)) {
            return $this->assertSoftDeleted($table->getTable(), [$table->getKeyName() => $table->getKey()], $table->getConnectionName(), $table->getDeletedAtColumn());
        }

        $this->assertThat(
            $this->getTable($table), new SoftDeletedInDatabase($this->getConnection($connection), $data, $deletedAtColumn)
        );

        return $this;
    }

    /**
     * Assert the given model exists in the database.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return $this
     */
    protected function assertModelExists($model)
    {
        return $this->assertDatabaseHas(
            $model->getTable(),
            [$model->getKeyName() => $model->getKey()],
            $model->getConnectionName()
        );
    }

    /**
     * Assert the given model does not exist in the database.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return $this
     */
    protected function assertModelMissing($model)
    {
        return $this->assertDatabaseMissing(
            $model->getTable(),
            [$model->getKeyName() => $model->getKey()],
            $model->getConnectionName()
        );
    }

    /**
     * Determine if the argument is a soft deletable model.
     *
     * @param  mixed  $model
     * @return bool
     */
    protected function isSoftDeletableModel($model)
    {
        return $model instanceof Model
            && in_array(SoftDeletes::class, class_uses_recursive($model));
    }

    /**
     * Cast a JSON string to a database compatible type.
     *
     * @param  array|string  $value
     * @return \Illuminate\Database\Query\Expression
     */
    public function castAsJson($value)
    {
        if ($value instanceof Jsonable) {
            $value = $value->toJson();
        } elseif (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        $value = DB::connection()->getPdo()->quote($value);

        return DB::raw("CAST($value AS JSON)");
    }

    /**
     * Get the database connection.
     *
     * @param  string|null  $connection
     * @return \Illuminate\Database\Connection
     */
    protected function getConnection($connection = null)
    {
        $database = $this->app->make('db');

        $connection = $connection ?: $database->getDefaultConnection();

        return $database->connection($connection);
    }

    /**
     * Get the table name from the given model or string.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $table
     * @return string
     */
    protected function getTable($table)
    {
        return is_subclass_of($table, Model::class) ? (new $table)->getTable() : $table;
    }

    /**
     * Seed a given database connection.
     *
     * @param  array|string  $class
     * @return $this
     */
    public function seed($class = 'Database\\Seeders\\DatabaseSeeder')
    {
        foreach (Arr::wrap($class) as $class) {
            $this->artisan('db:seed', ['--class' => $class, '--no-interaction' => true]);
        }

        return $this;
    }
}
