<?php

namespace Illuminate\Database;

use PDO;
use Exception;

/**
 * This is a wrapper for ODBC connections, which just proxies a different grammar, schema
 * and postprocessor.
 *
 * Class OdbcConnection
 */
class OdbcConnection extends Connection
{
    /**
     * @var \Illuminate\Database\Connection|null
     */
    private $internalConnectionType = null;

    public function __construct(PDO $pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        if (! isset($config['connectionType'])) {
            throw new Exception('Missing connection type.');
        }

        $connectionType = $config['connectionType'];
        if ($connectionType == self::class) {
            throw new Exception('Can\'t use '.self::class.' as connection type.');
        }

        $this->internalConnectionType = new $connectionType($pdo, $database, $tablePrefix, $config);

        if (! ($this->internalConnectionType instanceof Connection)) {
            throw new Exception('Unsupported connection type.');
        }

        parent::__construct($pdo, $database, $tablePrefix, $config);
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->internalConnectionType->getDefaultQueryGrammar();
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Schema\Grammars\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->internalConnectionType->getDefaultSchemaGrammar();
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return $this->internalConnectionType->getDefaultPostProcessor();
    }

    /**
     * Get the Doctrine DBAL driver.
     *
     * @return \Doctrine\DBAL\Driver\PDOSqlsrv\Driver
     */
    protected function getDoctrineDriver()
    {
        return new DoctrineDriver;
    }
}
