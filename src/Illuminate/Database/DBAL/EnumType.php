<?php

namespace Illuminate\Database\DBAL;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class EnumType extends Type
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return;
    }

    public function getName()
    {
        return 'enum';
    }
}