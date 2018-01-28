<?php

namespace Illuminate\Database\Schema\Columns;

/**
 * Class Text
 *
 * @property-read string $charset
 * @property-read string $collation
 * @method Text after(string $column)
 * @method Text comment(string $comment)
 * @method Text default(mixed $default)
 * @method Text first()
 * @method Text nullable(bool $value = true)
 * @method Text storedAs(string $expression)
 * @method Text virtualAs(string $expression)
 * @method Text change()
 * @method Text primary(?string $name = null)
 * @method Text unique(?string $name = null)
 * @method Text index(?string $name = null)
 * @method Text spatialIndex(?string $name = null)
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
     * @return Text
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
     * @return Text
     */
    public function collation(string $value): Text
    {
        $this->collation = $value;
        return $this;
    }
}
