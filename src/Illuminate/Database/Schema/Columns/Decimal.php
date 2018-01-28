<?php

namespace Illuminate\Database\Schema\Columns;

/**
 * Class Decimal
 *
 * @property-read int|null $total
 * @property-read int|null $places
 * @property-read bool $unsigned
 * @method Decimal after(string $column)
 * @method Decimal collation(string $value)
 * @method Decimal comment(string $comment)
 * @method Decimal default(mixed $default)
 * @method Decimal first()
 * @method Decimal nullable(bool $value = true)
 * @method Decimal storedAs(string $expression)
 * @method Decimal virtualAs(string $expression)
 * @method Decimal change()
 * @method Decimal primary(?string $name = null)
 * @method Decimal unique(?string $name = null)
 * @method Decimal index(?string $name = null)
 * @method Decimal spatialIndex(?string $name = null)
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
     * @param int|null $total
     * @param int|null $places
     */
    public function __construct(string $type, string $name, ?int $total = null, ?int $places = null)
    {
        parent::__construct($type, $name);

        $this->total = $total;
        $this->places = $places;
    }

    /**
     * Set INTEGER columns as UNSIGNED (MySQL)
     *
     * @return Decimal
     */
    public function unsigned(): Decimal
    {
        $this->unsigned = true;
        return $this;
    }
}
