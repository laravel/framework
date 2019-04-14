<?php

namespace Illuminate\Database\Schema\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class TinyInteger extends Type
{
    /**
     * The name of the custom type.
     *
     * @var string
     */
    const NAME = 'tinyinteger';

    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param  array  $fieldDeclaration
     * @param  AbstractPlatform  $platform
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'TINYINT';
    }

    /**
     * Converts a value from its database representation to its PHP representation
     * of this type.
     *
     * @param  mixed  $value
     * @param  AbstractPlatform  $platform
     * @return mixed
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param  mixed  $value
     * @param  AbstractPlatform  $platform
     * @return mixed
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    /**
     * The name of the custom type.
     *
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}