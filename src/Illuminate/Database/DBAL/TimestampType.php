<?php

namespace Illuminate\Database\DBAL;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class TimestampType extends Type
{
    public function getName()
    {
        return 'timestamp';
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        $name = $platform->getName();

        // See https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html
        switch ($name) {
            case 'mssql':
                return $this->getMSSQLPlatformSQLDeclaration($fieldDeclaration);

            case 'mysql':
            case 'mysql2':
                return $this->getMySQLPlatformSQLDeclaration($fieldDeclaration);

            case 'postgresql':
            case 'pgsql':
            case 'postgres':
                return $this->getPostgreSQLPlatformSQLDeclaration($fieldDeclaration);

            case 'sqlite':
            case 'sqlite3':
                return $this->getSqlitePlatformSQLDeclaration($fieldDeclaration);

            default:
                throw new DBALException('Invalid platform: '.$name);
        }
    }

    // https://docs.microsoft.com/en-us/sql/t-sql/data-types/rowversion-transact-sql?redirectedfrom=MSDN&view=sql-server-ver15
    // timestamp in MSSQL is not a field for storing datetime data
    protected function getMSSQLPlatformSQLDeclaration(array $fieldDeclaration)
    {
        $columnType = 'DATETIME';

        if ($fieldDeclaration['precision']) {
            $columnType = 'DATETIME2('.$fieldDeclaration['precision'].')';
        }

        return $columnType;
    }

    protected function getMySQLPlatformSQLDeclaration(array $fieldDeclaration)
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

    protected function getPostgreSQLPlatformSQLDeclaration(array $fieldDeclaration)
    {
        $columnType = 'TIMESTAMP('.(int) $fieldDeclaration['precision'].')';

        return $columnType;
    }

    /**
     * Laravel creates timestamps as datetime in SQLite.
     *
     * SQLite does not store microseconds without custom hacks.
     */
    protected function getSqlitePlatformSQLDeclaration(array $fieldDeclaration)
    {
        $columnType = 'DATETIME';

        return $columnType;
    }
}
