<?php

use Mockery as m;
use Illuminate\Database\Query\Builder;

class DatabaseJoinMemoryLeakTest extends PHPUnit_Framework_TestCase {

    public function tearDown()
    {
        m::close();
    }

    public function testItDoesNotLeakMemoryOnNewQuery()
    {
        $builderMain = $this->getBuilder();

        $this->runMemoryTest(function() use($builderMain){
            $builder = $builderMain->newQuery();
            $builder->select('*')->from('users');

        });
    }

    public function testItDoesNotLeakMemoryOnNewQueryWithJoin()
    {
        $builderMain = $this->getBuilder();

        $this->runMemoryTest(function() use($builderMain){
            $builder = $builderMain->newQuery();
            $builder->select('*')->join('new', 'col', '=', 'col2')->from('users');

        });
    }

    protected function runMemoryTest(\Closure $callback)
    {
        $i = 5;

        $last = null;

        while($i--)
        {
            $callback();

            $prev = $last;
            $last = memory_get_usage();
        }

        $this->assertEquals($prev, $last);
    }


    protected function getBuilder()
    {
        $grammar = new Illuminate\Database\Query\Grammars\SqlServerGrammar;
        $processor = m::mock('Illuminate\Database\Query\Processors\Processor');
        return new Builder(m::mock('Illuminate\Database\ConnectionInterface'), $grammar, $processor);
    }

}
