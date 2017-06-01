<?php

namespace Illuminate\Foundation\Pagination;

use Illuminate\Http\Request;

trait PaginatesModels
{
    /**
     * The default amount of items per page.
     *
     * @var int
     */
    protected $defaultCount = 100;

    /**
     * The default column to order by.
     *
     * @var string
     */
    protected $defaultOrderCol = 'created_at';

    /**
     * The default direction to order by.
     *
     * @var string
     */
    protected $defaultOrderDir = 'asc';

    /**
     * Get a paginator using filters from the request.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @param  \Illuminate\Http\Request|null  $request
     * @return \Illuminate\Pagination\Paginator
     */
    protected function paginate($model, Request $request = null)
    {
        $request = $request ?: app('request');

        $count = $this->getCount($request);
        $columns = $this->getColumns($request);
        $includes = $this->getIncludes($request);
        $orderBy = $this->getOrderBy($request);

        $items = ! empty($includes) ? $model::with(...$includes) : new $model;

        return $items->orderBy($orderBy['col'], $orderBy['dir'])
            ->paginate($count, $columns)
            ->appends($this->getAppends($request));
    }

    /**
     * Get the amount of items per page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    private function getCount(Request $request)
    {
        return (int) $request->input('count', $this->defaultCount);
    }

    /**
     * Limit the columns per model.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    private function getColumns(Request $request)
    {
        return $request->has('columns') ? explode(',', $request->input('columns')) : ['*'];
    }

    /**
     * Include specific model relationships.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|null
     */
    private function getIncludes(Request $request)
    {
        return $request->has('include') ? explode(',', $request->input('include')) : null;
    }

    /**
     * Order the items by a certain column and direction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    private function getOrderBy(Request $request)
    {
        $orderBy = explode('|', $request->input('orderBy', $this->defaultOrderCol.'|'.$this->defaultOrderDir));

        if (count($orderBy) === 1) {
            array_push($orderBy, $this->defaultOrderDir);
        }

        return [
            'col' => $orderBy[0],
            'dir' => $orderBy[1],
        ];
    }

    /**
     * Get the parameters to append to the pagination object's query string.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    private function getAppends(Request $request)
    {
        return [
            'count'   => $request->input('count'),
            'columns' => $request->input('columns'),
            'include' => $request->input('include'),
            'orderBy' => $request->input('orderBy'),
        ];
    }
}
