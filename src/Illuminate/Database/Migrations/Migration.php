<?php

namespace Illuminate\Database\Migrations;

use Symfony\Component\Console\Output\OutputInterface;

abstract class Migration
{
    /**
     * The name of the database connection to use.
     *
     * @var string|null
     */
    protected $connection;

    /**
     * Enables, if supported, wrapping the migration within a transaction.
     *
     * @var bool
     */
    public $withinTransaction = true;

    /**
     * The output interface implementation.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface|null
     */
    protected $output;

    /**
     * Get the migration connection name.
     *
     * @return string|null
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Set the output implementation that should be used by the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }
}
