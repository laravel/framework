<?php

namespace Illuminate\Database\Concerns;

use Doctrine\DBAL\Platforms\AbstractPlatform;

trait ManagesTypeMapping
{
    /**
     * A map of database column types.
     *
     * @var array
     */
    protected $typeMappings = [
        'bit' => 'string',
        'citext' => 'string',
        'enum' => 'string',
        'geometry' => 'string',
        'geomcollection' => 'string',
        'linestring' => 'string',
        'ltree' => 'string',
        'multilinestring' => 'string',
        'multipoint' => 'string',
        'multipolygon' => 'string',
        'point' => 'string',
        'polygon' => 'string',
        'sysname' => 'string',
    ];

    /**
     * Register the custom Doctrine type mappings for inspection commands.
     */
    protected function registerTypeMappings(AbstractPlatform $platform): void
    {
        foreach ($this->typeMappings as $type => $value) {
            $platform->registerDoctrineTypeMapping($type, $value);
        }
    }
}
