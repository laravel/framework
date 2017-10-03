<?php

namespace Illuminate\Database\Eloquent;

use ArrayAccess;
use BadMethodCallException;
use Exception;
use Illuminate\Contracts\Queue\QueueableEntity;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JsonSerializable;

/**
 * Class Model
 * @package Illuminate\Database\Eloquent
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Illuminate\Database\Query\Builder
 *
 * @see \Illuminate\Database\Eloquent\Builder
 * @method static \Illuminate\Database\Eloquent\Model make(array $attributes = []) Create and return an un-saved model instance.
 * @method static $this withGlobalScope(string $identifier, \Illuminate\Database\Eloquent\Scope | \Closure $scope) Register a new global scope.
 * @method static $this withoutGlobalScope(\Illuminate\Database\Eloquent\Scope | string $scope) Remove a registered global scope.
 * @method static $this withoutGlobalScopes(array $scopes = null) Remove all or passed registered global scopes.
 * @method static array removedScopes() Get an array of global scopes that were removed from the query.
 * @method static $this whereKey(mixed $id) Add a where clause on the primary key to the query.
 * @method static $this whereKeyNot(mixed $id) Add a where clause on the primary key to the query.
 * @method static $this where(string | array | \Closure $column, string $operator = null, mixed $value = null, string $boolean = 'and') Add a basic where clause to the query.
 * @method static \Illuminate\Database\Eloquent\Builder|static orWhere(\Closure | array | string $column, string $operator = null, mixed $value = null) Add an "or where" clause to the query.
 * @method static \Illuminate\Database\Eloquent\Collection hydrate(array $items) Create a collection of models from plain arrays.
 * @method static \Illuminate\Database\Eloquent\Collection fromQuery(string $query, array $bindings = []) Create a collection of models from a raw query.
 * @method static \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|static[]|static|null find(mixed $id, array $columns = ['*']) Find a model by its primary key.
 * @method static \Illuminate\Database\Eloquent\Collection findMany(\Illuminate\Contracts\Support\Arrayable | array $ids, array $columns = ['*']) Find multiple models by their primary keys.
 * @method static \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection findOrFail(mixed $id, array $columns = []) Find a model by its primary key or throw an exception.
 * @method static \Illuminate\Database\Eloquent\Model findOrNew(mixed $id, array $columns = ['*']) Find a model by its primary key or return fresh model instance.
 * @method static \Illuminate\Database\Eloquent\Model firstOrNew(array $attributes, array $values = []) Get the first record matching the attributes or instantiate it.
 * @method static \Illuminate\Database\Eloquent\Model firstOrCreate(array $attributes, array $values = []) Get the first record matching the attributes or create it.
 * @method static \Illuminate\Database\Eloquent\Model updateOrCreate(array $attributes, array $values = []) Create or update a record matching the attributes, and fill it with values.
 * @method static \Illuminate\Database\Eloquent\Model|static firstOrFail(array $columns = ['*']) Execute the query and get the first result or throw an exception.
 * @method static \Illuminate\Database\Eloquent\Model|static|mixed firstOr(\Closure | array $columns=null, \Closure $callback = null) Execute the query and get the first result or call a callback.
 * @method static mixed value(string $column) Get a single column's value from the first result of a query.
 * @method static \Illuminate\Database\Eloquent\Collection|static[] get(array $columns = ['*']) Execute the query as a "select" statement.
 * @method static \Illuminate\Database\Eloquent\Model[] getModels(array $columns = ['*']) Get the hydrated models without eager loading.
 * @method static array eagerLoadRelations(array $models) Eager load the relationships for the models.
 * @method static \Generator cursor() Get a generator for the given query.
 * @method static bool chunkById(int $count, callable $callback, string $column = null, string $alias = null) Chunk the results of a query by comparing numeric IDs.
 * @method static \Illuminate\Support\Collection pluck(string $column, string $key = null) Get an array with the values of a given column.
 * @method static \Illuminate\Contracts\Pagination\LengthAwarePaginator paginate(int $perPage = null, array $columns = ['*'], string $pageName = 'page', int $page = null) Paginate the given query.
 * @method static \Illuminate\Contracts\Pagination\Paginator simplePaginate(int $perPage = null, array $columns = ['*'], string $pageName = 'page', int $page = null) Paginate the given query into a simple paginator.
 * @method static \Illuminate\Database\Eloquent\Model|$this create(array $attributes = []) Save a new model and return the instance.
 * @method static \Illuminate\Database\Eloquent\Model|$this forceCreate(array $attributes) Save a new model and return the instance. Allow mass-assignment.
 * @method static void onDelete(\Closure $callback) Register a replacement for the default delete function.
 * @method static mixed scopes(array $scopes) Call the given local model scopes.
 * @method static \Illuminate\Database\Eloquent\Builder|static applyScopes() Apply the scopes to the Eloquent builder instance and return it.
 * @method static $this without(mixed $relations) Prevent the specified relations from being eager loaded.
 * @method static \Illuminate\Database\Eloquent\Model newModelInstance(array $attributes = []) Create a new instance of the model being queried.
 * @method static \Illuminate\Database\Query\Builder getQuery() Get the underlying query builder instance.
 * @method static $this setQuery(\Illuminate\Database\Query\Builder $query) Set the underlying query builder instance.
 * @method static \Illuminate\Database\Query\Builder toBase() Get a base query builder instance.
 * @method static array getEagerLoads() Get the relationships being eagerly loaded.
 * @method static $this setEagerLoads(array $eagerLoad) Set the relationships being eagerly loaded.
 * @method static \Illuminate\Database\Eloquent\Model getModel() Get the model instance being queried.
 * @method static $this setModel(\Illuminate\Database\Eloquent\Model $model) Set a model instance for the model being queried.
 * @method static \Closure getMacro(string $name) Get the given macro by name.
 *
 * @see \Illuminate\Database\Concerns\BuildsQueries
 * @method static bool chunk(int $count, callable $callback) Chunk the results of the query.
 * @method static bool each(callable $callback, int $count = 1000) Execute a callback over each item while chunking.
 * @method static \Illuminate\Database\Eloquent\Model|static|null first(array $columns = ['*']) Execute the query and get the first result.
 * @method static mixed when(mixed $value, callable $callback, callable $default = null) Apply the callback's query changes if the given "value" is true.
 * @method static \Illuminate\Database\Query\Builder tap(\Closure $callback) Pass the query to a given callback.
 * @method static mixed unless(mixed $value, callable $callback, callable $default = null) Apply the callback's query changes if the given "value" is false.
 *
 * @see \Illuminate\Database\Concerns\QueriesRelationships
 * @method static \Illuminate\Database\Eloquent\Builder|static has(string $relation, string $operator = '>=', int $count = 1, string $boolean = 'and', Closure $callback = null) Add a relationship count / exists condition to the query.
 * @method static \Illuminate\Database\Eloquent\Builder|static orHas(string $relation, string $operator = '>=', int $count = 1) Add a relationship count / exists condition to the query with an "or".
 * @method static \Illuminate\Database\Eloquent\Builder|static doesntHave(string $relation, string $boolean = 'and', Closure $callback = null) Add a relationship count / exists condition to the query.
 * @method static \Illuminate\Database\Eloquent\Builder|static orDoesntHave(string $relation) Add a relationship count / exists condition to the query with an "or".
 * @method static \Illuminate\Database\Eloquent\Builder|static whereHas(string $relation, Closure $callback = null, string $operator = '>=', int $count = 1) Add a relationship count / exists condition to the query with where clauses.
 * @method static \Illuminate\Database\Eloquent\Builder|static orWhereHas(string $relation, Closure $callback = null, string $operator = '>=', int $count = 1) Add a relationship count / exists condition to the query with where clauses and an "or".
 * @method static \Illuminate\Database\Eloquent\Builder|static whereDoesntHave(string $relation, \Closure $callback = null) Add a relationship count / exists condition to the query with where clauses.
 * @method static \Illuminate\Database\Eloquent\Builder|static orWhereDoesntHave(string $relation, \Closure $callback) Add a relationship count / exists condition to the query with where clauses and an "or".
 * @method static $this withCount(mixed $relations) Add subselect queries to count the relations.
 * @method static \Illuminate\Database\Eloquent\Builder|static mergeConstraintsFrom(\Illuminate\Database\Eloquent\Builder $from) Merge the where constraints from another query to the current query.
 *
 * @see \Illuminate\Database\Query\Builder
 * @method static $this select(array | mixed $columns = ['*']) Set the columns to be selected.
 * @method static \Illuminate\Database\Query\Builder|static selectRaw(string $expression, array $bindings = []) Add a new "raw" select expression to the query.
 * @method static \Illuminate\Database\Query\Builder|static selectSub(\Closure | \Illuminate\Database\Query\Builder | string $query, string $as) Add a subselect expression to the query.
 * @method static $this addSelect(array | mixed $column) Add a new select column to the query.
 * @method static $this distinct() Force the query to only return distinct results.
 * @method static $this from(string $table) Set the table which the query is targeting.
 * @method static $this join(string $table, string $first, string $operator = null, string $second = null, string $type = 'inner', bool $where = false) Add a join clause to the query.
 * @method static \Illuminate\Database\Query\Builder|static joinWhere(string $table, string $first, string $operator, string $second, string $type = 'inner') Add a "join where" clause to the query.
 * @method static \Illuminate\Database\Query\Builder|static leftJoin(string $table, string $first, string $operator = null, string $second = null) Add a left join to the query.
 * @method static \Illuminate\Database\Query\Builder|static leftJoinWhere(string $table, string $first, string $operator, string $second) Add a "join where" clause to the query.
 * @method static \Illuminate\Database\Query\Builder|static rightJoin(string $table, string $first, string $operator = null, string $second = null) Add a right join to the query.
 * @method static \Illuminate\Database\Query\Builder|static rightJoinWhere(string $table, string $first, string $operator, string $second) Add a "right join where" clause to the query.
 * @method static \Illuminate\Database\Query\Builder|static crossJoin(string $table, string $first = null, string $operator = null, string $second = null) Add a "cross join" clause to the query.
 * @method static void mergeWheres(array $wheres, array $bindings) Merge an array of where clauses and bindings.
 * @method static \Illuminate\Database\Query\Builder|static whereColumn(string | array $first, string $operator = null, string $second = null, string $boolean = 'and') Add a "where" clause comparing two columns to the query.
 * @method static \Illuminate\Database\Query\Builder|static orWhereColumn(string | array $first, string $operator = null, string $second = null) Add an "or where" clause comparing two columns to the query.
 * @method static $this whereRaw(string $sql, mixed $bindings = [], string $boolean = 'and') Add a raw where clause to the query.
 * @method static \Illuminate\Database\Query\Builder|static orWhereRaw(string $sql, mixed $bindings = []) Add a raw or where clause to the query.
 * @method static $this whereIn(string $column, mixed $values, string $boolean = 'and', bool $not = false) Add a "where in" clause to the query.
 * @method static \Illuminate\Database\Query\Builder|static orWhereIn(string $column, mixed $values) Add an "or where in" clause to the query.
 * @method static \Illuminate\Database\Query\Builder|static whereNotIn(string $column, mixed $values, string $boolean = 'and') Add a "where not in" clause to the query.
 * @method static \Illuminate\Database\Query\Builder|static orWhereNotIn(string $column, mixed $values) Add an "or where not in" clause to the query.
 * @method static $this whereNull(string $column, string $boolean = 'and', bool $not = false) Add a "where null" clause to the query.
 * @method static \Illuminate\Database\Query\Builder|static orWhereNull(string $column) Add an "or where null" clause to the query.
 * @method static \Illuminate\Database\Query\Builder|static whereNotNull(string $column, string $boolean = 'and') Add a "where not null" clause to the query.
 * @method static $this whereBetween(string $column, array $values, string $boolean = 'and', bool $not = false) Add a where between statement to the query.
 * @method static \Illuminate\Database\Query\Builder|static orWhereBetween(string $column, array $values) Add an or where between statement to the query.
 * @method static \Illuminate\Database\Query\Builder|static whereNotBetween(string $column, array $values, string $boolean = 'and') Add a where not between statement to the query.
 * @method static \Illuminate\Database\Query\Builder|static orWhereNotBetween(string $column, array $values) Add an or where not between statement to the query.
 * @method static \Illuminate\Database\Query\Builder|static orWhereNotNull(string $column) Add an "or where not null" clause to the query.
 * @method static \Illuminate\Database\Query\Builder|static whereDate(string $column, string $operator, mixed $value = null, string $boolean = 'and') Add a "where date" statement to the query.
 * @method static \Illuminate\Database\Query\Builder|static orWhereDate(string $column, string $operator, string $value) Add an "or where date" statement to the query.
 * @method static \Illuminate\Database\Query\Builder|static whereTime(string $column, string $operator, int $value, string $boolean = 'and') Add a "where time" statement to the query.
 * @method static \Illuminate\Database\Query\Builder|static orWhereTime(string $column, string $operator, int $value) Add an "or where time" statement to the query.
 * @method static \Illuminate\Database\Query\Builder|static whereDay(string $column, string $operator, mixed $value = null, string $boolean = 'and') Add a "where day" statement to the query.
 * @method static \Illuminate\Database\Query\Builder|static whereMonth(string $column, string $operator, mixed $value = null, string $boolean = 'and') Add a "where month" statement to the query.
 * @method static \Illuminate\Database\Query\Builder|static whereYear(string $column, string $operator, mixed $value = null, string $boolean = 'and') Add a "where year" statement to the query.
 * @method static \Illuminate\Database\Query\Builder|static whereNested(\Closure $callback, string $boolean = 'and') Add a nested where statement to the query.
 * @method static \Illuminate\Database\Query\Builder forNestedWhere() Create a new query instance for nested where condition.
 * @method static $this addNestedWhereQuery(\Illuminate\Database\Query\Builder $query, string $boolean = 'and') Add another query builder as a nested where to the query builder.
 * @method static $this whereExists(\Closure $callback, string $boolean = 'and', bool $not = false) Add an exists clause to the query.
 * @method static \Illuminate\Database\Query\Builder|static orWhereExists(\Closure $callback, bool $not = false) Add an or exists clause to the query.
 * @method static \Illuminate\Database\Query\Builder|static whereNotExists(\Closure $callback, string $boolean = 'and') Add a where not exists clause to the query.
 * @method static \Illuminate\Database\Query\Builder|static orWhereNotExists(\Closure $callback) Add a where not exists clause to the query.
 * @method static $this addWhereExistsQuery(\Illuminate\Database\Query\Builder $query, $boolean = 'and', $not = false) Add an exists clause to the query.
 * @method static $this dynamicWhere(string $method, string $parameters) Handles dynamic "where" clauses to the query.
 * @method static $this groupBy(...$groups) Add a "group by" clause to the query.
 * @method static $this having(string $column, string $operator = null, string $value = null, string $boolean = 'and') Add a "having" clause to the query.
 * @method static \Illuminate\Database\Query\Builder|static orHaving(string $column, string $operator = null, string $value = null) Add a "or having" clause to the query.
 * @method static $this havingRaw(string $sql, array $bindings = [], string $boolean = 'and') Add a raw having clause to the query.
 * @method static \Illuminate\Database\Query\Builder|static orHavingRaw(string $sql, array $bindings = []) Add a raw or having clause to the query.
 * @method static $this orderBy(string $column, string $direction = 'asc') Add an "order by" clause to the query.
 * @method static $this orderByDesc(string $column) Add a descending "order by" clause to the query.
 * @method static \Illuminate\Database\Query\Builder|static latest(string $column = 'created_at') Add an "order by" clause for a timestamp to the query.
 * @method static \Illuminate\Database\Query\Builder|static oldest(string $column = 'created_at') Add an "order by" clause for a timestamp to the query.
 * @method static $this inRandomOrder(string $seed = '') Put the query's results in random order.
 * @method static $this orderByRaw(string $sql, array $bindings = []) Add a raw "order by" clause to the query.
 * @method static \Illuminate\Database\Query\Builder|static skip(int $value) Alias to set the "offset" value of the query.
 * @method static $this offset(int $value) Set the "offset" value of the query.
 * @method static \Illuminate\Database\Query\Builder|static take(int $value) Alias to set the "limit" value of the query.
 * @method static $this limit(int $value) Set the "limit" value of the query.
 * @method static \Illuminate\Database\Query\Builder|static forPage(int $page, int $perPage = 15) Set the limit and offset for a given page.
 * @method static \Illuminate\Database\Query\Builder|static forPageAfterId($perPage = 15, $lastId = 0, $column = 'id') Constrain the query to the next "page" of results after a given ID.
 * @method static \Illuminate\Database\Query\Builder|static union(\Illuminate\Database\Query\Builder | \Closure $query, bool $all = false) Add a union statement to the query.
 * @method static \Illuminate\Database\Query\Builder|static unionAll(\Illuminate\Database\Query\Builder | \Closure $query) Add a union all statement to the query.
 * @method static $this lock(string | bool $value = true) Lock the selected rows in the table.
 * @method static \Illuminate\Database\Query\Builder lockForUpdate() Lock the selected rows in the table for updating.
 * @method static \Illuminate\Database\Query\Builder sharedLock() Share lock the selected rows in the table.
 * @method static string toSql() Get the SQL representation of the query.
 * @method static int getCountForPagination(array $columns = ['*']) Get the count of the total records for the paginator.
 * @method static string implode(string $column, string $glue='') Concatenate values of a given column as a string.
 * @method static bool exists() Determine if any rows exist for the current query.
 * @method static int count(array|string $columns = '*') Retrieve the "count" result of the query.
 * @method static mixed min(string $column) Retrieve the minimum value of a given column.
 * @method static mixed max(string $column) Retrieve the maximum value of a given column.
 * @method static mixed sum(string $column) Retrieve the sum of the values of a given column.
 * @method static mixed avg(string $column) Retrieve the average of the values of a given column.
 * @method static mixed average(string $column) Alias for the "avg" method.
 * @method static mixed aggregate(string $function, array $columns = ['*']) Execute an aggregate function on the database.
 * @method static float|int numericAggregate(string $function, array $columns = ['*']) Execute a numeric aggregate function on the database.
 * @method static bool insert(array $values) Insert a new record into the database.
 * @method static int insertGetId(array $values, string $sequence = null) Insert a new record and get the value of the primary key.
 * @method static bool updateOrInsert(array $attributes, array $values = []) Insert or update a record matching the attributes, and fill it with values.
 * @method static void truncate() Run a truncate statement on the table.
 * @method static \Illuminate\Database\Query\Expression raw(mixed $value) Create a raw database expression.
 * @method static array getBindings() Get the current query value bindings in a flattened array.
 * @method static array getRawBindings() Get the raw array of bindings.
 * @method static $this setBindings(array $bindings, string $type = 'where') Set the bindings on the query builder.
 * @method static $this addBinding(mixed $value, string $type = 'where') Add a binding to the query.
 * @method static $this mergeBindings(\Illuminate\Database\Query\Builder $query) Merge an array of bindings into our bindings.
 * @method static \Illuminate\Database\Query\Processors\Processor getProcessor() Get the database query processor instance.
 * @method static \Illuminate\Database\Query\Grammars\Grammar getGrammar() Get the query grammar instance.
 * @method static $this useWritePdo() Use the write pdo for query.
 * @method static static cloneWithout(array $properties) Clone the query without the given properties.
 * @method static static cloneWithoutBindings(array $except) Clone the query without the given bindings.
 *
 * @see \Illuminate\Support\Traits\Macroable
 * @method static void macro(string $name, object | callable $macro) Register a custom macro.
 * @method static void mixin(object $mixin) Mix another object into the class.
 * @method static bool hasMacro(string $name) Checks if macro is registered.
 * @method static mixed macroCall(string $method, array $parameters) Dynamically handle calls to the class.
 *
 */
abstract class Model implements ArrayAccess, Arrayable, Jsonable, JsonSerializable, QueueableEntity, UrlRoutable
{
    use Concerns\HasAttributes,
        Concerns\HasEvents,
        Concerns\HasGlobalScopes,
        Concerns\HasRelationships,
        Concerns\HasTimestamps,
        Concerns\HidesAttributes,
        Concerns\GuardsAttributes;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [];

    /**
     * The relationship counts that should be eager loaded on every query.
     *
     * @var array
     */
    protected $withCount = [];

    /**
     * The number of models to return for pagination.
     *
     * @var int
     */
    protected $perPage = 15;

    /**
     * Indicates if the model exists.
     *
     * @var bool
     */
    public $exists = false;

    /**
     * Indicates if the model was inserted during the current request lifecycle.
     *
     * @var bool
     */
    public $wasRecentlyCreated = false;

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected static $resolver;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected static $dispatcher;

    /**
     * The array of booted models.
     *
     * @var array
     */
    protected static $booted = [];

    /**
     * The array of global scopes on the model.
     *
     * @var array
     */
    protected static $globalScopes = [];

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->bootIfNotBooted();

        $this->syncOriginal();

        $this->fill($attributes);
    }

    /**
     * Check if the model needs to be booted and if so, do it.
     *
     * @return void
     */
    protected function bootIfNotBooted()
    {
        if (! isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;

            $this->fireModelEvent('booting', false);

            static::boot();

            $this->fireModelEvent('booted', false);
        }
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        static::bootTraits();
    }

    /**
     * Boot all of the bootable traits on the model.
     *
     * @return void
     */
    protected static function bootTraits()
    {
        $class = static::class;

        foreach (class_uses_recursive($class) as $trait) {
            if (method_exists($class, $method = 'boot'.class_basename($trait))) {
                forward_static_call([$class, $method]);
            }
        }
    }

    /**
     * Clear the list of booted models so they will be re-booted.
     *
     * @return void
     */
    public static function clearBootedModels()
    {
        static::$booted = [];

        static::$globalScopes = [];
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param  array  $attributes
     * @return $this
     *
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function fill(array $attributes)
    {
        $totallyGuarded = $this->totallyGuarded();

        foreach ($this->fillableFromArray($attributes) as $key => $value) {
            $key = $this->removeTableFromKey($key);

            // The developers may choose to place some attributes in the "fillable" array
            // which means only those attributes may be set through mass assignment to
            // the model, and all others will just get ignored for security reasons.
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            } elseif ($totallyGuarded) {
                throw new MassAssignmentException($key);
            }
        }

        return $this;
    }

    /**
     * Fill the model with an array of attributes. Force mass assignment.
     *
     * @param  array  $attributes
     * @return $this
     */
    public function forceFill(array $attributes)
    {
        return static::unguarded(function () use ($attributes) {
            return $this->fill($attributes);
        });
    }

    /**
     * Remove the table name from a given key.
     *
     * @param  string  $key
     * @return string
     */
    protected function removeTableFromKey($key)
    {
        return Str::contains($key, '.') ? last(explode('.', $key)) : $key;
    }

    /**
     * Create a new instance of the given model.
     *
     * @param  array  $attributes
     * @param  bool  $exists
     * @return static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        // This method just provides a convenient way for us to generate fresh model
        // instances of this current model. It is particularly useful during the
        // hydration of new objects via the Eloquent query builder instances.
        $model = new static((array) $attributes);

        $model->exists = $exists;

        $model->setConnection(
            $this->getConnectionName()
        );

        return $model;
    }

    /**
     * Create a new model instance that is existing.
     *
     * @param  array  $attributes
     * @param  string|null  $connection
     * @return static
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $model = $this->newInstance([], true);

        $model->setRawAttributes((array) $attributes, true);

        $model->setConnection($connection ?: $this->getConnectionName());

        $model->fireModelEvent('retrieved', false);

        return $model;
    }

    /**
     * Begin querying the model on a given connection.
     *
     * @param  string|null  $connection
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function on($connection = null)
    {
        // First we will just create a fresh instance of this model, and then we can
        // set the connection on the model so that it is be used for the queries
        // we execute, as well as being set on each relationship we retrieve.
        $instance = new static;

        $instance->setConnection($connection);

        return $instance->newQuery();
    }

    /**
     * Begin querying the model on the write connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public static function onWriteConnection()
    {
        $instance = new static;

        return $instance->newQuery()->useWritePdo();
    }

    /**
     * Get all of the models from the database.
     *
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function all($columns = ['*'])
    {
        return (new static)->newQuery()->get(
            is_array($columns) ? $columns : func_get_args()
        );
    }

    /**
     * Begin querying a model with eager loading.
     *
     * @param  array|string  $relations
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function with($relations)
    {
        return (new static)->newQuery()->with(
            is_string($relations) ? func_get_args() : $relations
        );
    }

    /**
     * Eager load relations on the model.
     *
     * @param  array|string  $relations
     * @return $this
     */
    public function load($relations)
    {
        $query = $this->newQuery()->with(
            is_string($relations) ? func_get_args() : $relations
        );

        $query->eagerLoadRelations([$this]);

        return $this;
    }

    /**
     * Eager load relations on the model if they are not already eager loaded.
     *
     * @param  array|string  $relations
     * @return $this
     */
    public function loadMissing($relations)
    {
        $relations = is_string($relations) ? func_get_args() : $relations;

        return $this->load(array_filter($relations, function ($relation) {
            return ! $this->relationLoaded($relation);
        }));
    }

    /**
     * Increment a column's value by a given amount.
     *
     * @param  string  $column
     * @param  int  $amount
     * @param  array  $extra
     * @return int
     */
    protected function increment($column, $amount = 1, array $extra = [])
    {
        return $this->incrementOrDecrement($column, $amount, $extra, 'increment');
    }

    /**
     * Decrement a column's value by a given amount.
     *
     * @param  string  $column
     * @param  int  $amount
     * @param  array  $extra
     * @return int
     */
    protected function decrement($column, $amount = 1, array $extra = [])
    {
        return $this->incrementOrDecrement($column, $amount, $extra, 'decrement');
    }

    /**
     * Run the increment or decrement method on the model.
     *
     * @param  string  $column
     * @param  int  $amount
     * @param  array  $extra
     * @param  string  $method
     * @return int
     */
    protected function incrementOrDecrement($column, $amount, $extra, $method)
    {
        $query = $this->newQuery();

        if (! $this->exists) {
            return $query->{$method}($column, $amount, $extra);
        }

        $this->incrementOrDecrementAttributeValue($column, $amount, $extra, $method);

        return $query->where(
            $this->getKeyName(), $this->getKey()
        )->{$method}($column, $amount, $extra);
    }

    /**
     * Increment the underlying attribute value and sync with original.
     *
     * @param  string  $column
     * @param  int  $amount
     * @param  array  $extra
     * @param  string  $method
     * @return void
     */
    protected function incrementOrDecrementAttributeValue($column, $amount, $extra, $method)
    {
        $this->{$column} = $this->{$column} + ($method == 'increment' ? $amount : $amount * -1);

        $this->forceFill($extra);

        $this->syncOriginalAttribute($column);
    }

    /**
     * Update the model in the database.
     *
     * @param  array  $attributes
     * @param  array  $options
     * @return bool
     */
    public function update(array $attributes = [], array $options = [])
    {
        if (! $this->exists) {
            return false;
        }

        return $this->fill($attributes)->save($options);
    }

    /**
     * Save the model and all of its relationships.
     *
     * @return bool
     */
    public function push()
    {
        if (! $this->save()) {
            return false;
        }

        // To sync all of the relationships to the database, we will simply spin through
        // the relationships and save each model via this "push" method, which allows
        // us to recurse into all of these nested relations for the model instance.
        foreach ($this->relations as $models) {
            $models = $models instanceof Collection
                        ? $models->all() : [$models];

            foreach (array_filter($models) as $model) {
                if (! $model->push()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = [])
    {
        $query = $this->newQueryWithoutScopes();

        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This provides a chance for any
        // listeners to cancel save operations if validations fail or whatever.
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        // If the model already exists in the database we can just update our record
        // that is already in this database using the current IDs in this "where"
        // clause to only update this model. Otherwise, we'll just insert them.
        if ($this->exists) {
            $saved = $this->isDirty() ?
                        $this->performUpdate($query) : true;
        }

        // If the model is brand new, we'll insert it into our database and set the
        // ID attribute on the model to the value of the newly inserted row's ID
        // which is typically an auto-increment value managed by the database.
        else {
            $saved = $this->performInsert($query);

            if (! $this->getConnectionName() &&
                $connection = $query->getConnection()) {
                $this->setConnection($connection->getName());
            }
        }

        // If the model is successfully saved, we need to do a few more things once
        // that is done. We will call the "saved" method here to run any actions
        // we need to happen after a model gets successfully saved right here.
        if ($saved) {
            $this->finishSave($options);
        }

        return $saved;
    }

    /**
     * Save the model to the database using transaction.
     *
     * @param  array  $options
     * @return bool
     *
     * @throws \Throwable
     */
    public function saveOrFail(array $options = [])
    {
        return $this->getConnection()->transaction(function () use ($options) {
            return $this->save($options);
        });
    }

    /**
     * Perform any actions that are necessary after the model is saved.
     *
     * @param  array  $options
     * @return void
     */
    protected function finishSave(array $options)
    {
        $this->fireModelEvent('saved', false);

        if ($this->isDirty() && ($options['touch'] ?? true)) {
            $this->touchOwners();
        }

        $this->syncOriginal();
    }

    /**
     * Perform a model update operation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performUpdate(Builder $query)
    {
        // If the updating event returns false, we will cancel the update operation so
        // developers can hook Validation systems into their models and cancel this
        // operation if the model does not pass validation. Otherwise, we update.
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        // First we need to create a fresh query instance and touch the creation and
        // update timestamp on the model which are maintained by us for developer
        // convenience. Then we will just continue saving the model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        // Once we have run the update operation, we will fire the "updated" event for
        // this model instance. This will allow developers to hook into these after
        // models are updated, giving them a chance to do any special processing.
        $dirty = $this->getDirty();

        if (count($dirty) > 0) {
            $this->setKeysForSaveQuery($query)->update($dirty);

            $this->fireModelEvent('updated', false);

            $this->syncChanges();
        }

        return true;
    }

    /**
     * Set the keys for a save update query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where($this->getKeyName(), '=', $this->getKeyForSaveQuery());

        return $query;
    }

    /**
     * Get the primary key value for a save query.
     *
     * @return mixed
     */
    protected function getKeyForSaveQuery()
    {
        return $this->original[$this->getKeyName()]
                        ?? $this->getKey();
    }

    /**
     * Perform a model insert operation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performInsert(Builder $query)
    {
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        // First we'll need to create a fresh query instance and touch the creation and
        // update timestamps on this model, which are maintained by us for developer
        // convenience. After, we will just continue saving these model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        // If the model has an incrementing key, we can use the "insertGetId" method on
        // the query builder, which will give us back the final inserted ID for this
        // table from the database. Not all tables have to be incrementing though.
        $attributes = $this->attributes;

        if ($this->getIncrementing()) {
            $this->insertAndSetId($query, $attributes);
        }

        // If the table isn't incrementing we'll simply insert these attributes as they
        // are. These attribute arrays must contain an "id" column previously placed
        // there by the developer as the manually determined key for these models.
        else {
            if (empty($attributes)) {
                return true;
            }

            $query->insert($attributes);
        }

        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
        $this->exists = true;

        $this->wasRecentlyCreated = true;

        $this->fireModelEvent('created', false);

        return true;
    }

    /**
     * Insert the given attributes and set the ID on the model.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $attributes
     * @return void
     */
    protected function insertAndSetId(Builder $query, $attributes)
    {
        $id = $query->insertGetId($attributes, $keyName = $this->getKeyName());

        $this->setAttribute($keyName, $id);
    }

    /**
     * Destroy the models for the given IDs.
     *
     * @param  array|int  $ids
     * @return int
     */
    public static function destroy($ids)
    {
        // We'll initialize a count here so we will return the total number of deletes
        // for the operation. The developers can then check this number as a boolean
        // type value or get this total count of records deleted for logging, etc.
        $count = 0;

        $ids = is_array($ids) ? $ids : func_get_args();

        // We will actually pull the models from the database table and call delete on
        // each of them individually so that their events get fired properly with a
        // correct set of attributes in case the developers wants to check these.
        /** @var string $key */
        /** @var Model $instance */
        $key = ($instance = new static)->getKeyName();

        foreach ($instance->whereIn($key, $ids)->get() as $model) {
            if ($model->delete()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Delete the model from the database.
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function delete()
    {
        if (is_null($this->getKeyName())) {
            throw new Exception('No primary key defined on model.');
        }

        // If the model doesn't exist, there is nothing to delete so we'll just return
        // immediately and not do anything else. Otherwise, we will continue with a
        // deletion process on the model, firing the proper events, and so forth.
        if (! $this->exists) {
            return NULL;
        }

        if ($this->fireModelEvent('deleting') === false) {
            return false;
        }

        // Here, we'll touch the owning models, verifying these timestamps get updated
        // for the models. This will allow any caching to get broken on the parents
        // by the timestamp. Then we will go ahead and delete the model instance.
        $this->touchOwners();

        $this->performDeleteOnModel();

        // Once the model has been deleted, we will fire off the deleted event so that
        // the developers may hook into post-delete operations. We will then return
        // a boolean true as the delete is presumably successful on the database.
        $this->fireModelEvent('deleted', false);

        return true;
    }

    /**
     * Force a hard delete on a soft deleted model.
     *
     * This method protects developers from running forceDelete when trait is missing.
     *
     * @return bool|null
     */
    public function forceDelete()
    {
        return $this->delete();
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function performDeleteOnModel()
    {
        $this->setKeysForSaveQuery($this->newQueryWithoutScopes())->delete();

        $this->exists = false;
    }

    /**
     * Begin querying the model.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function query()
    {
        return (new static)->newQuery();
    }

    /**
     * Get a new query builder for the model's table.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQuery()
    {
        $builder = $this->newQueryWithoutScopes();

        foreach ($this->getGlobalScopes() as $identifier => $scope) {
            $builder->withGlobalScope($identifier, $scope);
        }

        return $builder;
    }

    /**
     * Get a new query builder that doesn't have any global scopes.
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newQueryWithoutScopes()
    {
        $builder = $this->newEloquentBuilder($this->newBaseQueryBuilder());

        // Once we have the query builders, we will set the model instances so the
        // builder can easily access any information it may need from the model
        // while it is constructing and executing various queries against it.
        return $builder->setModel($this)
                    ->with($this->with)
                    ->withCount($this->withCount);
    }

    /**
     * Get a new query instance without a given scope.
     *
     * @param  \Illuminate\Database\Eloquent\Scope|string  $scope
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQueryWithoutScope($scope)
    {
        $builder = $this->newQuery();

        return $builder->withoutGlobalScope($scope);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        return new QueryBuilder(
            $connection, $connection->getQueryGrammar(), $connection->getPostProcessor()
        );
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * Create a new pivot model instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  array  $attributes
     * @param  string  $table
     * @param  bool  $exists
     * @param  string|null  $using
     * @return \Illuminate\Database\Eloquent\Relations\Pivot
     */
    public function newPivot(Model $parent, array $attributes, $table, $exists, $using = null)
    {
        /** @var $using Pivot */
        return $using ? $using::fromRawAttributes($parent, $attributes, $table, $exists)
                      : Pivot::fromAttributes($parent, $attributes, $table, $exists);
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge($this->attributesToArray(), $this->relationsToArray());
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param  int  $options
     * @return string
     *
     * @throws \Illuminate\Database\Eloquent\JsonEncodingException
     */
    public function toJson($options = 0)
    {
        $json = json_encode($this->jsonSerialize(), $options);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw JsonEncodingException::forModel($this, json_last_error_msg());
        }

        return $json;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Reload a fresh model instance from the database.
     *
     * @param  array|string  $with
     * @return static|null
     */
    public function fresh($with = [])
    {
        if (! $this->exists) {
            return NULL;
        }

        return static::newQueryWithoutScopes()
                        ->with(is_string($with) ? func_get_args() : $with)
                        ->where($this->getKeyName(), $this->getKey())
                        ->first();
    }

    /**
     * Reload the current model instance with fresh attributes from the database.
     *
     * @return $this
     */
    public function refresh()
    {
        if (! $this->exists) {
            return $this;
        }

        $this->load(array_keys($this->relations));

        $this->setRawAttributes(static::findOrFail($this->getKey())->attributes);

        return $this;
    }

    /**
     * Clone the model into a new, non-existing instance.
     *
     * @param  array|null  $except
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function replicate(array $except = null)
    {
        $defaults = [
            $this->getKeyName(),
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ];

        $attributes = Arr::except(
            $this->attributes, $except ? array_unique(array_merge($except, $defaults)) : $defaults
        );

        return tap(new static, function (Model $instance) use ($attributes) {
            $instance->setRawAttributes($attributes);

            $instance->setRelations($this->relations);
        });
    }

    /**
     * Determine if two models have the same ID and belong to the same table.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return bool
     */
    public function is($model)
    {
        return ! is_null($model) &&
               $this->getKey() === $model->getKey() &&
               $this->getTable() === $model->getTable() &&
               $this->getConnectionName() === $model->getConnectionName();
    }

    /**
     * Determine if two models are not the same.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return bool
     */
    public function isNot($model)
    {
        return ! $this->is($model);
    }

    /**
     * Get the database connection for the model.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return static::resolveConnection($this->getConnectionName());
    }

    /**
     * Get the current connection name for the model.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connection;
    }

    /**
     * Set the connection associated with the model.
     *
     * @param  string  $name
     * @return $this
     */
    public function setConnection($name)
    {
        $this->connection = $name;

        return $this;
    }

    /**
     * Resolve a connection instance.
     *
     * @param  string|null  $connection
     * @return \Illuminate\Database\Connection
     */
    public static function resolveConnection($connection = null)
    {
        return static::$resolver->connection($connection);
    }

    /**
     * Get the connection resolver instance.
     *
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    public static function getConnectionResolver()
    {
        return static::$resolver;
    }

    /**
     * Set the connection resolver instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public static function setConnectionResolver(Resolver $resolver)
    {
        static::$resolver = $resolver;
    }

    /**
     * Unset the connection resolver for models.
     *
     * @return void
     */
    public static function unsetConnectionResolver()
    {
        static::$resolver = null;
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        if (! isset($this->table)) {
            return str_replace('\\', '', Str::snake(Str::plural(class_basename($this))));
        }

        return $this->table;
    }

    /**
     * Set the table associated with the model.
     *
     * @param  string  $table
     * @return $this
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->primaryKey;
    }

    /**
     * Set the primary key for the model.
     *
     * @param  string  $key
     * @return $this
     */
    public function setKeyName($key)
    {
        $this->primaryKey = $key;

        return $this;
    }

    /**
     * Get the table qualified key name.
     *
     * @return string
     */
    public function getQualifiedKeyName()
    {
        return $this->getTable().'.'.$this->getKeyName();
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType()
    {
        return $this->keyType;
    }

    /**
     * Set the data type for the primary key.
     *
     * @param  string  $type
     * @return $this
     */
    public function setKeyType($type)
    {
        $this->keyType = $type;

        return $this;
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return $this->incrementing;
    }

    /**
     * Set whether IDs are incrementing.
     *
     * @param  bool  $value
     * @return $this
     */
    public function setIncrementing($value)
    {
        $this->incrementing = $value;

        return $this;
    }

    /**
     * Get the value of the model's primary key.
     *
     * @return mixed
     */
    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    /**
     * Get the queueable identity for the entity.
     *
     * @return mixed
     */
    public function getQueueableId()
    {
        return $this->getKey();
    }

    /**
     * Get the queueable connection for the entity.
     *
     * @return mixed
     */
    public function getQueueableConnection()
    {
        return $this->getConnectionName();
    }

    /**
     * Get the value of the model's route key.
     *
     * @return mixed
     */
    public function getRouteKey()
    {
        return $this->getAttribute($this->getRouteKeyName());
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return $this->getKeyName();
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value)
    {
        return $this->where($this->getRouteKeyName(), $value)->first();
    }

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return Str::snake(class_basename($this)).'_'.$this->primaryKey;
    }

    /**
     * Get the number of models to return per page.
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * Set the number of models to return per page.
     *
     * @param  int  $perPage
     * @return $this
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return ! is_null($this->getAttribute($offset));
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset], $this->relations[$offset]);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, ['increment', 'decrement'])) {
            return $this->$method(...$parameters);
        }

        try {
            return $this->newQuery()->$method(...$parameters);
        } catch (BadMethodCallException $e) {
            throw new BadMethodCallException(
                sprintf('Call to undefined method %s::%s()', get_class($this), $method)
            );
        }
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    /**
     * Convert the model to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * When a model is being unserialized, check if it needs to be booted.
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->bootIfNotBooted();
    }
}
