<?php

namespace Illuminate\Database;

use PDO;

class FetchMode
{
    public function __construct(
        protected array $arguments = [],
    ) {
        //
    }

    /**
     * The arguments.
     *
     * @return array
     */
    public function arguments()
    {
        return $this->arguments;
    }

    /**
     * Retrieve a single column.
     *
     * @param int  $position
     * @return self
     */
    public static function column(int $position = 0): self
    {
        return new self([PDO::FETCH_COLUMN, $position]);
    }

    /**
     * Use the first SELECT column as the array key, and the second SELECT column as the value.
     *
     * @return self
     */
    public static function pair(): self
    {
        return new self([PDO::FETCH_KEY_PAIR]);
    }

    /**
     * Use the first SELECT column as the array key. This column is consumed in the process.
     *
     * @return self
     */
    public static function keyed(): self
    {
        return new self([PDO::FETCH_UNIQUE]);
    }

    public static function cursor($nth = 2): self
    {
        return new self([PDO::FETCH_OBJ, PDO::FETCH_ORI_REL, $nth]);
    }


}
