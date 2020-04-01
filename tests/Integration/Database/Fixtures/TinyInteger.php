<?php

namespace Illuminate\Tests\Integration\Database\Fixtures;

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
     * @param  \Doctrine\DBAL\Platforms\AbstractPlatform  $platform
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'TINYINT';
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
