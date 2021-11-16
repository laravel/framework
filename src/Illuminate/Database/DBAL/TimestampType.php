<?php

namespace Illuminate\Database\DBAL;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\PhpDateTimeMappingType;
use Doctrine\DBAL\Types\Type;

class TimestampType extends Type implements PhpDateTimeMappingType
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        $name = $platform->getName();

        switch ($name) {
            case 'mysql':
            case 'mysql2':
                return $this->getMySqlPlatformSQLDeclaration($fieldDeclaration);

            case 'postgresql':
            case 'pgsql':
            case 'postgres':
                return $this->getPostgresPlatformSQLDeclaration($fieldDeclaration);

            case 'mssql':
                return $this->getSqlServerPlatformSQLDeclaration($fieldDeclaration);

            case 'sqlite':
            case 'sqlite3':
                return $this->getSQLitePlatformSQLDeclaration($fieldDeclaration);

            default:
                throw new DBALException('Invalid platform: '.$name);
        }
    }

    /**
     * Get the SQL declaration for MySQL.
     *
     * @param  array  $fieldDeclaration
     * @return string
     */
    protected function getMySqlPlatformSQLDeclaration(array $fieldDeclaration)
    {
        $columnType = 'TIMESTAMP';

        if ($fieldDeclaration['precision']) {
            $columnType = 'TIMESTAMP('.$fieldDeclaration['precision'].')';
        }

        $notNull = $fieldDeclaration['notnull'] ?? false;

        if (! $notNull) {
            return $columnType.' NULL';
        }

        return $columnType;
    }

    /**
     * Get the SQL declaration for PostgreSQL.
     *
     * @param  array  $fieldDeclaration
     * @return string
     */
    protected function getPostgresPlatformSQLDeclaration(array $fieldDeclaration)
    {
        return 'TIMESTAMP('.(int) $fieldDeclaration['precision'].')';
    }

    /**
     * Get the SQL declaration for SQL Server.
     *
     * @param  array  $fieldDeclaration
     * @return string
     */
    protected function getSqlServerPlatformSQLDeclaration(array $fieldDeclaration)
    {
        return $fieldDeclaration['precision'] ?? false
                    ? 'DATETIME2('.$fieldDeclaration['precision'].')'
                    : 'DATETIME';
    }

    /**
     * Get the SQL declaration for SQLite.
     *
     * @param  array  $fieldDeclaration
     * @return string
     */
    protected function getSQLitePlatformSQLDeclaration(array $fieldDeclaration)
    {
        return 'DATETIME';
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
