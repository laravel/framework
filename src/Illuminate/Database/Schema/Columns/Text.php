<?php

namespace Illuminate\Database\Schema\Columns;

/**
 * Class Text
 *
 * @property-read string $charset
 * @property-read string $collation
 * @method \Illuminate\Database\Schema\Columns\Text after(string $column)
 * @method \Illuminate\Database\Schema\Columns\Text comment(string $comment)
 * @method \Illuminate\Database\Schema\Columns\Text default(mixed $default)
 * @method \Illuminate\Database\Schema\Columns\Text first()
 * @method \Illuminate\Database\Schema\Columns\Text nullable(bool $value = true)
 * @method \Illuminate\Database\Schema\Columns\Text storedAs(string $expression)
 * @method \Illuminate\Database\Schema\Columns\Text virtualAs(string $expression)
 * @method \Illuminate\Database\Schema\Columns\Text change()
 * @method \Illuminate\Database\Schema\Columns\Text primary(?string $name = null)
 * @method \Illuminate\Database\Schema\Columns\Text unique(?string $name = null)
 * @method \Illuminate\Database\Schema\Columns\Text index(?string $name = null)
 * @method \Illuminate\Database\Schema\Columns\Text spatialIndex(?string $name = null)
 */
class Text extends Column
{
    /**
     * @var string
     */
    protected $charset;

    /**
     * @var string
     */
    private $collation;

    /**
     * Specify a character set for the column (MySQL)
     *
     * @param string $charset
     * @return \Illuminate\Database\Schema\Columns\Text
     */
    public function charset(string $charset): Text
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * Specify a collation for the column (MySQL/SQL Server)
     *
     * @param string $value
     * @return \Illuminate\Database\Schema\Columns\Text
     */
    public function collation(string $value): Text
    {
        $this->collation = $value;
        return $this;
    }
}
