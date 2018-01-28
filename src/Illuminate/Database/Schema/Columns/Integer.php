<?php

namespace Illuminate\Database\Schema\Columns;

/**
 * Class Integer
 *
 * @property-read bool $autoIncrement
 * @property-read bool $unsigned
 * @method \Illuminate\Database\Schema\Columns\Integer after(string $column)
 * @method \Illuminate\Database\Schema\Columns\Integer comment(string $comment)
 * @method \Illuminate\Database\Schema\Columns\Integer default(mixed $default)
 * @method \Illuminate\Database\Schema\Columns\Integer first()
 * @method \Illuminate\Database\Schema\Columns\Integer nullable(bool $value = true)
 * @method \Illuminate\Database\Schema\Columns\Integer storedAs(string $expression)
 * @method \Illuminate\Database\Schema\Columns\Integer virtualAs(string $expression)
 * @method \Illuminate\Database\Schema\Columns\Integer change()
 * @method \Illuminate\Database\Schema\Columns\Integer primary(?string $name = null)
 * @method \Illuminate\Database\Schema\Columns\Integer unique(?string $name = null)
 * @method \Illuminate\Database\Schema\Columns\Integer index(?string $name = null)
 * @method \Illuminate\Database\Schema\Columns\Integer spatialIndex(?string $name = null)
 */
class Integer extends Column
{
    /**
     * @var bool
     */
    protected $autoIncrement = false;

    /**
     * @var bool
     */
    protected $unsigned = false;

    /**
     * Integer constructor.
     *
     * @param string $type
     * @param string $name
     * @param bool $autoIncrement
     * @param bool $unsigned
     */
    public function __construct(string $type, string $name, bool $autoIncrement = false, bool $unsigned = false)
    {
        parent::__construct($type, $name);

        $this->autoIncrement = $autoIncrement;
        $this->unsigned = $unsigned;
    }

    /**
     * Set INTEGER columns as auto-increment (primary key)
     *
     * @return \Illuminate\Database\Schema\Columns\Integer
     */
    public function autoIncrement(): \Illuminate\Database\Schema\Columns\Integer
    {
        $this->autoIncrement = true;
        return $this;
    }

    /**
     * Set INTEGER columns as UNSIGNED (MySQL)
     *
     * @return \Illuminate\Database\Schema\Columns\Integer
     */
    public function unsigned(): \Illuminate\Database\Schema\Columns\Integer
    {
        $this->unsigned = true;
        return $this;
    }
}
