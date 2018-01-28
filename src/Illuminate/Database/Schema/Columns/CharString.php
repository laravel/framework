<?php

namespace Illuminate\Database\Schema\Columns;

/**
 * Class CharString
 *
 * @property-read int $length
 */
class CharString extends Text
{
    /**
     * @var int
     */
    protected $length;

    /**
     * CharString constructor.
     *
     * @param string $type
     * @param string $name
     * @param int $length
     */
    public function __construct(string $type, string $name, int $length)
    {
        parent::__construct($type, $name);

        $this->length = $length;
    }
}
