<?php

namespace Illuminate\Database\Schema\Columns;

/**
 * Class Decimal
 *
 * @property-read int|null $total
 * @property-read int|null $places
 * @property-read bool $unsigned
 * @method \Illuminate\Database\Schema\Columns\Decimal after(string $column)
 * @method \Illuminate\Database\Schema\Columns\Decimal comment(string $comment)
 * @method \Illuminate\Database\Schema\Columns\Decimal default(mixed $default)
 * @method \Illuminate\Database\Schema\Columns\Decimal first()
 * @method \Illuminate\Database\Schema\Columns\Decimal nullable(bool $value = true)
 * @method \Illuminate\Database\Schema\Columns\Decimal storedAs(string $expression)
 * @method \Illuminate\Database\Schema\Columns\Decimal virtualAs(string $expression)
 * @method \Illuminate\Database\Schema\Columns\Decimal change()
 * @method \Illuminate\Database\Schema\Columns\Decimal primary(string $name = null)
 * @method \Illuminate\Database\Schema\Columns\Decimal unique(string $name = null)
 * @method \Illuminate\Database\Schema\Columns\Decimal index(string $name = null)
 * @method \Illuminate\Database\Schema\Columns\Decimal spatialIndex(string $name = null)
 */
class Decimal extends Column
{
    /**
     * @var int|null
     */
    protected $total;

    /**
     * @var int|null
     */
    protected $places;

    /**
     * @var bool
     */
    protected $unsigned = false;

    /**
     * Decimal constructor.
     *
     * @param string $type
     * @param string $name
     * @param int $total
     * @param int $places
     */
    public function __construct(string $type, string $name, int $total = null, int $places = null)
    {
        parent::__construct($type, $name);

        $this->total = $total;
        $this->places = $places;
    }

    /**
     * Set INTEGER columns as UNSIGNED (MySQL)
     *
     * @return \Illuminate\Database\Schema\Columns\Decimal
     */
    public function unsigned(): Decimal
    {
        $this->unsigned = true;
        return $this;
    }
}
