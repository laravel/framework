<?php

/**
 * Class OrderByMock
 *
 * Class that mimics SQL-like orderBy function
 */
class OrderByMock
{
    public $migrations = [];
    private $orderingColumns = [];

    public function orderBy($column, $order)
    {
        $this->orderingColumns[] = $column;
        usort($this->migrations, function ($a, $b) use ($order) {
            $_a = $_b = '';
            foreach ($this->orderingColumns as $column) {
                $_a .= $a[$column];
                $_b .= $b[$column];
            }

            if ($order === 'asc') {
                return strcmp($_a, $_b);
            } else {
                return strcmp($_b, $_a);
            }
        });

        return $this;
    }

    public function lists($column)
    {
        return array_pluck($this->migrations, $column);
    }
}
