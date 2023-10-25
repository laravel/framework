<?php

namespace Illuminate\Database\DBAL;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\Exception\NotSupported;
use Doctrine\DBAL\Platforms\MariaDB1052Platform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Types\PhpDateTimeMappingType;
use Doctrine\DBAL\Types\Type;

class TimestampType extends Type implements PhpDateTimeMappingType
{
    /**
     * {@inheritdoc}
     *
     * @throws \Doctrine\DBAL\Platforms\Exception\NotSupported
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return match (get_class($platform)) {
            MySQLPlatform::class,
            MySQL80Platform::class,
            MariaDBPlatform::class,
            MariaDB1052Platform::class, => $this->getMySqlPlatformSQLDeclaration($column),
            PostgreSQLPlatform::class => $this->getPostgresPlatformSQLDeclaration($column),
            SQLServerPlatform::class => $this->getSqlServerPlatformSQLDeclaration($column),
            SQLitePlatform::class => 'DATETIME',
            default => throw NotSupported::new('TIMESTAMP'),
        };
    }

    /**
     * Get the SQL declaration for MySQL.
     *
     * @param  array  $column
     * @return string
     */
    protected function getMySqlPlatformSQLDeclaration(array $column): string
    {
        $columnType = 'TIMESTAMP';

        if ($column['precision']) {
            $columnType = 'TIMESTAMP('.min((int) $column['precision'], 6).')';
        }

        $notNull = $column['notnull'] ?? false;

        if (! $notNull) {
            return $columnType.' NULL';
        }

        return $columnType;
    }

    /**
     * Get the SQL declaration for PostgreSQL.
     *
     * @param  array  $column
     * @return string
     */
    protected function getPostgresPlatformSQLDeclaration(array $column): string
    {
        return 'TIMESTAMP('.min((int) $column['precision'], 6).')';
    }

    /**
     * Get the SQL declaration for SQL Server.
     *
     * @param  array  $column
     * @return string
     */
    protected function getSqlServerPlatformSQLDeclaration(array $column): string
    {
        return $column['precision'] ?? false
            ? 'DATETIME2('.min((int) $column['precision'], 7).')'
            : 'DATETIME';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'timestamp';
    }
}
