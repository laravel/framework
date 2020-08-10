<?php

namespace Illuminate\Support;

class Sort
{
    /**
     * The items to be sorted.
     *
     * @var array
     */
    protected $items = [];

    /**
     * The sort parameters.
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * @var callable
     */
    private $valueRetriever;

    /**
     * Create a new sort.
     *
     * @param  array  $items
     * @param  callable  $valueRetriever
     */
    public function __construct(array $items, callable $valueRetriever)
    {
        $this->items = $items;
        $this->valueRetriever = $valueRetriever;
    }

    /**
     * Add a sort in ascending order using the given callback.
     *
     * @param  callable|string  $callback
     * @param  int  $options
     * @return static
     */
    public function asc($callback, $options = SORT_REGULAR)
    {
        return $this->sort($callback, $options);
    }

    /**
     * Add a sort in descending order using the given callback.
     *
     * @param  callable|string  $callback
     * @param  int  $options
     * @return static
     */
    public function desc($callback, $options = SORT_REGULAR)
    {
        return $this->sort($callback, $options, true);
    }

    /**
     * Add a sort using the given callback.
     *
     * @param  callable|string  $callback
     * @param  int  $options
     * @param  bool  $descending
     * @return static
     */
    public function sort($callback, $options = SORT_REGULAR, $descending = false)
    {
        $this->parameters[] = [$callback, $options, $descending ? SORT_DESC : SORT_ASC];

        return $this;
    }

    /**
     * Get the sorted items.
     *
     * @return array
     */
    public function get()
    {
        $parameters = [];

        foreach ($this->parameters as [$callback, $options, $direction]) {
            $column = array_map(
                ($this->valueRetriever)($callback),
                $this->items,
                array_keys($this->items)
            );

            $parameters = array_merge($parameters, [$column, $options, $direction]);
        }
        $parameters[] = &$this->items;

        array_multisort(...$parameters);

        return $this->items;
    }
}
