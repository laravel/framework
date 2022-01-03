<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Container\Container;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class Pagination
{
    /**
     * The type of paginator to build.
     *
     * @var string
     */
    protected $type;

    /**
     * The number of items to retrieve per page.
     *
     * @var int
     */
    protected $perPage;

    /**
     * The page number or cursor value.
     *
     * @var string|int|\Illuminate\Pagination\Cursor
     */
    protected $location;

    /**
     * The name of the page or cursor to capture and set in the Request.
     *
     * @var string
     */
    protected $pageName;

    /**
     * The order to enforce in the query when paginating by a Cursor.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $orders;

    /**
     * Create a new Pagination instance.
     *
     * @param  string  $type
     * @param  string  $pageName
     * @return void
     */
    public function __construct($type, $pageName)
    {
        $this->pageName = $pageName;
        $this->type = $type;
    }

    /**
     * Sets the number of items to retrieve per page.
     *
     * @param  int|null  $perPage
     * @param  string|int|\Illuminate\Pagination\Cursor|null  $page
     * @param  string|null  $pageName
     * @return $this
     */
    public function perPage($perPage, $page = null, $pageName = null)
    {
        $this->perPage = $perPage;

        if ($page) {
            $this->page($page);
        }

        if ($pageName) {
            $this->pageName($pageName);
        }

        return $this;
    }

    /**
     * Sets the page for the pagination.
     *
     * @param  int  $page
     * @return $this
     */
    public function page($page)
    {
        $this->location = $page;

        return $this;
    }

    /**
     * Sets the cursor for the pagination.
     *
     * @param  string|int|\Illuminate\Pagination\Cursor  $cursor
     * @return $this
     */
    public function cursor($cursor)
    {
        return $this->page($cursor);
    }

    /**
     * Sets the name of the page to capture from the request.
     *
     * @param  string|null  $pageName
     * @return $this
     */
    public function pageName($pageName)
    {
        $this->pageName = $pageName;

        return $this;
    }

    /**
     * Sets the name of the cursor to capture from the request.
     *
     * @param  string|null  $cursorName
     * @return $this
     */
    public function cursorName($cursorName)
    {
        return $this->pageName($cursorName);
    }

    /**
     * Builds and returns a query with the stored pagination constraints.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function buildPaginatedQuery($relation)
    {
        $this->perPage = $this->perPage ?? $relation->getModel()->getPerPage();

        switch ($this->type) {
            case 'page': default: return $this->pagination($relation);
            case 'simple': return $this->simplePagination($relation);
            case 'cursor': return $this->cursorPagination($relation);
        }
    }

    /**
     * Returns a paginated query.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    protected function pagination($relation)
    {
        $this->location = $this->location ?? Paginator::resolveCurrentPage($this->pageName);

        return $relation->forPage($this->location, $this->perPage);
    }

    /**
     * Returns a simple paginated query.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    protected function simplePagination($relation)
    {
        $this->location = $this->location ?? Paginator::resolveCurrentPage($this->pageName);

        return $relation->skip(($this->location - 1) * $this->perPage)->take($this->perPage + 1);
    }

    /**
     * Returns a cursor paginated query.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    protected function cursorPagination($relation)
    {
        if (! $this->location instanceof Cursor) {
            $this->location = is_string($this->location)
                ? Cursor::fromEncoded($this->location)
                : CursorPaginator::resolveCurrentCursor($this->pageName, $this->location);
        }

        $this->orders = $this->ensureOrderForCursorPagination(
            $relation, ! is_null($this->location) && $this->location->pointsToPreviousItems()
        );

        if (! is_null($this->location)) {
            $addCursorConditions = function ($query, $previousColumn, $i) use (&$addCursorConditions) {

                if (! is_null($previousColumn)) {
                    $query->where(
                        $this->getOriginalColumnNameForCursorPagination($query, $previousColumn),
                        '=',
                        $this->location->parameter($previousColumn)
                    );
                }

                $query->where(function ($query) use ($addCursorConditions, $i) {
                    ['column' => $column, 'direction' => $direction] = $this->orders[$i];

                    $query->where(
                        $this->getOriginalColumnNameForCursorPagination($query, $column),
                        $direction === 'asc' ? '>' : '<',
                        $this->location->parameter($column)
                    );

                    if ($i < $this->orders->count() - 1) {
                        $query->orWhere(function ($query) use ($addCursorConditions, $column, $i) {
                            $addCursorConditions($query, $column, $i + 1);
                        });
                    }
                });
            };

            $addCursorConditions($relation, null, 0);
        }

        return $relation->limit($this->perPage + 1);
    }

    /**
     * Get the original column name of the given column, without any aliasing.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $builder
     * @param  string  $parameter
     * @return string
     */
    protected function getOriginalColumnNameForCursorPagination($builder, string $parameter)
    {
        $columns = $builder instanceof Builder ? $builder->getQuery()->columns : $builder->columns;

        if (! is_null($columns)) {
            foreach ($columns as $column) {
                if (($position = stripos($column, ' as ')) !== false) {
                    $as = substr($column, $position, 4);

                    [$original, $alias] = explode($as, $column);

                    if ($parameter === $alias) {
                        return $original;
                    }
                }
            }
        }

        return $parameter;
    }

    /**
     * Ensure the proper order by required for cursor pagination.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @param  bool  $shouldReverse
     * @return \Illuminate\Support\Collection
     */
    protected function ensureOrderForCursorPagination($relation, $shouldReverse = false)
    {
        $orders = collect($relation->getBaseQuery()->orders);

        if ($orders->count() === 0) {
            $this->enforceOrderBy($relation);
        }

        if ($shouldReverse) {
            $relation->getBaseQuery()->orders = collect($relation->getBaseQuery()->orders)->map(function ($order) {
                $order['direction'] = $order['direction'] === 'asc' ? 'desc' : 'asc';

                return $order;
            })->toArray();
        }

        return collect($relation->getBaseQuery()->orders);
    }

    /**
     * Add a generic "order by" clause if the query doesn't already have one.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @return void
     */
    protected function enforceOrderBy($relation)
    {
        if (empty($relation->getBaseQuery()->orders) && empty($relation->getBaseQuery()->unionOrders)) {
            $relation->orderBy($relation->getModel()->getQualifiedKeyName(), 'asc');
        }
    }

    /**
     * Wraps the results into their respective Paginator.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @param  \Illuminate\Database\Eloquent\Collection  $result
     * @return \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Pagination\Paginator|\Illuminate\Pagination\CursorPaginator
     */
    public function wrapIntoPaginator($relation, Collection $result)
    {
        switch ($this->type) {
            case 'page':
            default:
                $total = $relation->toBase()->getCountForPagination();

                return static::createPaginator($result, $total, $this->perPage, $this->location, [
                    'path' => Paginator::resolveCurrentPath(),
                    'pageName' => $this->pageName,
                ]);
            case 'simple':
                return static::createSimplePaginator($result, $this->perPage, $this->location, [
                    'path' => Paginator::resolveCurrentPath(),
                    'pageName' => $this->pageName,
                ]);
            case 'cursor':
                return static::createCursorPaginator($result, $this->perPage, $this->location, [
                    'path' => Paginator::resolveCurrentPath(),
                    'cursorName' => $this->pageName,
                    'parameters' => $this->orders->pluck('column')->toArray(),
                ]);
        }
    }

    /**
     * Wraps the results into a paginator.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $items
     * @param  int  $total
     * @param  int  $perPage
     * @param  int  $currentPage
     * @param  array  $options
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public static function createPaginator($items, $total, $perPage, $currentPage, $options)
    {
        return Container::getInstance()->makeWith(LengthAwarePaginator::class, compact(
            'items', 'total', 'perPage', 'currentPage', 'options'
        ));
    }

    /**
     * Wraps the results into a simple paginator.
     *
     * @param  \Illuminate\Support\Collection  $items
     * @param  int  $perPage
     * @param  int  $currentPage
     * @param  array  $options
     * @return \Illuminate\Pagination\Paginator
     */
    public static function createSimplePaginator($items, $perPage, $currentPage, $options)
    {
        return Container::getInstance()->makeWith(Paginator::class, compact(
            'items', 'perPage', 'currentPage', 'options'
        ));
    }

    /**
     * Wraps the results into a paginator.
     *
     * @param  \Illuminate\Support\Collection  $items
     * @param  int  $perPage
     * @param  \Illuminate\Pagination\Cursor  $cursor
     * @param  array  $options
     * @return \Illuminate\Pagination\CursorPaginator
     */
    public static function createCursorPaginator($items, $perPage, $cursor, $options)
    {
        return Container::getInstance()->makeWith(CursorPaginator::class, compact(
            'items', 'perPage', 'cursor', 'options'
        ));
    }
}
