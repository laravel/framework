<?php

namespace Illuminate\Database\Schema\Columns;

/**
 * Class VariableLength
 *
 * This class is used to store params of columns created by Blueprint::char and Blueprint::string methods
 * @property-read int $length
 */
class VariableLength extends Text
{
    /**
     * @var int
     */
    protected $length;

    /**
     * VariableLength constructor.
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
