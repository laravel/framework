<?php

namespace Illuminate\Database\Schema\Types;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type as DoctrineType;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar;
use Illuminate\Support\Arr;

abstract class Type extends DoctrineType
{
    /**
     * A mapping used to determine the grammar for the platform.
     *
     * @var array
     */
    protected $mapping = [
        'mysql' => MySqlGrammar::class,
        'postgresql' => PostgresGrammar::class,
        'sqlite' => SQLiteGrammar::class,
        'mssql' => SqlServerGrammar::class
    ];

    /**
     * Get the schema grammar linked to this platform.
     *
     * @param  string  $name
     * @return \Illuminate\Database\Schema\Grammars\Grammar
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getSchemaGrammar($name)
    {
        if (Arr::exists($this->mapping, $name)) {
            return new $this->mapping[$name];
        }

        throw DBALException::notSupported('getSchemaGrammar');
    }
}
