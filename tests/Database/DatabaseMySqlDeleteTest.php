<?php

use Mockery as m;
use Illuminate\Database\Query\Builder;

class DatabaseMySqlDeleteTest extends PHPUnit_Framework_TestCase
{

    public $builder = null;

    public $processor = null;

    public $query_builder = null;


    protected function getBuilder()
    {
        if ( ! $this->query_builder) {
            $this->grammar = new Illuminate\Database\Query\Grammars\MySqlGrammar;
            $this->processor = m::mock('Illuminate\Database\Query\Processors\Processor');
            $this->query_builder = new Builder(m::mock('Illuminate\Database\ConnectionInterface'), $this->grammar,
                $this->processor);
        }

        return $this->query_builder;
    }


    /**
     * Mysql delete queries with joins should have the alias name between DELETE and FROM
     * Valid: DELETE a FROM join_test AS a INNER JOIN join_test AS b ON (a.id < b.id)
     * Invalid: DELETE join_test AS a FROM join_test AS a INNER JOIN join_test AS b ON (a.id < b.id)
     */
    public function testMysqlJoinDeleteWithAlias()
    {
        $builder = $this->getBuilder();
        $builder->from('join_test AS a')->join('join_test AS b', 'a.id', '<', 'b.id');
        $query = $this->grammar->compileDelete($builder);
        $this->assertFalse(strpos(strstr($query, " from ", true), 'join_test'),
            "Delete query with alias should only have the alias between delete and from");
    }
}

