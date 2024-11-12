<?php

namespace Illuminate\Database;

use Exception;
use Illuminate\Database\Query\Grammars\DmGrammar as QueryGrammar;
use Illuminate\Database\Query\Processors\DmProcessor;
use Illuminate\Database\Schema\Grammars\DmGrammar as SchemaGrammar;
use Illuminate\Database\Schema\DmBuilder;
use Illuminate\Database\Schema\DmSchemaState;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use PDO;

class DmConnection extends Connection
{
    /**
     * @var string
     */
    protected $schema;

    /**
     * {@inheritdoc}
     */
    public function getDriverTitle()
    {
        return 'Dm';
    }

    /**
     * @param  PDO|\Closure  $pdo
     * @param  string  $database
     * @param  string  $tablePrefix
     * @param  array  $config
     */
    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        parent::__construct($pdo, $database, $tablePrefix, $config);
        $this->schema = $config['schema'] ? $config['schema'] : $config['username'];
    }

    /**
     * Get current schema.
     *
     * @return string
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Set current schema.
     *
     * @param  string  $schema
     * @return $this
     */
    public function setSchema($schema)
    {
        $this->schema = $schema;
        $sessionVars = [
            'CURRENT_SCHEMA' => $schema,
        ];

        return $this->setSessionVars($sessionVars);
    }

    /**
     * Update session variables.
     *
     * @param  array  $sessionVars
     * @return $this
     */
    public function setSessionVars(array $sessionVars)
    {
        $vars = [];
        foreach ($sessionVars as $option => $value) {
            if (strtoupper($option) == 'CURRENT_SCHEMA' || strtoupper($option) == 'EDITION') {
                $vars[] = "$option  = $value";
            } else {
                $vars[] = "$option  = '$value'";
            }
        }

        foreach ($vars as $var) {
            $sql = 'ALTER SESSION SET '.$var;
            $this->statement($sql);
        }

        return $this;
    }

    /**
     * Set session date format.
     *
     * @param  string  $format
     * @return $this
     */
    public function setDateFormat($format = 'YYYY-MM-DD HH24:MI:SS')
    {
        $sessionVars = [
            'NLS_DATE_FORMAT'      => $format,
            'NLS_TIMESTAMP_FORMAT' => $format,
        ];

        return $this->setSessionVars($sessionVars);
    }

    /**
     * Get config schema prefix.
     *
     * @return string
     */
    protected function getConfigSchemaPrefix()
    {
        return isset($this->config['prefix_schema']) ? $this->config['prefix_schema'] : '';
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\DmGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        ($grammar = new QueryGrammar)->setConnection($this);
                
        return $this->withTablePrefix($grammar);
    }


    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Illuminate\Database\Schema\DmBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new DmBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Schema\Grammars\DmGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        ($grammar = new SchemaGrammar)->setConnection($this);

        return $this->withTablePrefix($grammar);
    }

    /**
     * Get the schema state for the connection.
     *
     * @param  \Illuminate\Filesystem\Filesystem|null  $files
     * @param  callable|null  $processFactory
     * @return \Illuminate\Database\Schema\DmSchemaState
     */
    public function getSchemaState(?Filesystem $files = null, ?callable $processFactory = null)
    {
        return new DmSchemaState($this, $files, $processFactory);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\DmProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new DmProcessor;
    }
}
