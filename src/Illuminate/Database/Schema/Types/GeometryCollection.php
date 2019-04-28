<?php

namespace Illuminate\Database\Schema\Types;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class GeometryCollection extends Type
{
    /**
     * The name of the custom type.
     *
     * @var string
     */
    const NAME = 'geometrycollection';

    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param  array  $fieldDeclaration
     * @param  \Doctrine\DBAL\Platforms\AbstractPlatform  $platform
     * @return string
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        $grammar = $this->getSchemaGrammar(
            $platform->getName()
        );

        if (! method_exists($grammar, 'doctrineTypeGeometryCollection')) {
            throw DBALException::notSupported('doctrineTypeGeometryCollection');
        }

        return $grammar->doctrineTypeGeometryCollection(
            $fieldDeclaration
        );
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