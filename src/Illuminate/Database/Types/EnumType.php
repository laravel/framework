<?php

namespace Illuminate\Database\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Illuminate\Support\Arr;

class EnumType extends Type
{
    public const ENUM = 'enum';

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        $options = Arr::get($fieldDeclaration, 'allowedOptions', []);
        $optionsString = count($options) ? "'".implode("','", $options)."'" : "''";

        return "ENUM({$optionsString})";
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::ENUM;
    }
}
