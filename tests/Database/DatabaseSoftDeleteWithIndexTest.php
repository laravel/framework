<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Schema\Blueprint;

class DatabaseSoftDeleteWithIndexTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testSoftDeletesWithIndex()
    {
        $blueprint = new Blueprint('test');

        $connection = m::mock('Illuminate\Database\Connection');

        $blueprint->softDeletes('deleted_at', 0, true);
        $blueprint->index(['deleted_at'], 'deleted_at_idx');
        $statements = $blueprint->toSql($connection, new \Illuminate\Database\Schema\Grammars\MySqlGrammar);
        $this->assertStringContainsString('alter table `test` add `deleted_at` timestamp null', $statements[0]);
        $this->assertStringContainsString('alter table `test` add index `deleted_at_idx`(`deleted_at`)', $statements[1]);
    }

    public function testSoftDeletesWithoutIndex()
    {
        $blueprint = new Blueprint('test');
        $connection = m::mock('Illuminate\Database\Connection');

        $blueprint->softDeletes('deleted_at', 0, false);
        $blueprint->index(['deleted_at'], 'deleted_at_idx');
        $statements = $blueprint->toSql($connection, new \Illuminate\Database\Schema\Grammars\MySqlGrammar);
        
        $this->assertStringContainsString('`deleted_at` timestamp null', $statements[0]);
        $this->assertCount(2, $statements);
    }

    public function testSoftDeletesTzWithIndex()
    {
        $blueprint = new Blueprint('test');
        $connection = m::mock('Illuminate\Database\Connection');

        $blueprint->softDeletesTz('deleted_at', 0, true);
        $blueprint->index(['deleted_at'], 'deleted_at_idx');
        $statements = $blueprint->toSql($connection, new \Illuminate\Database\Schema\Grammars\MySqlGrammar);

        $this->assertStringContainsString('`deleted_at` timestamp null', $statements[0]);
        $this->assertStringContainsString('alter table `test` add index `deleted_at_idx`(`deleted_at`)', $statements[1]);
    }

    public function testSoftDeletesTzWithoutIndex()
    {
        $blueprint = new Blueprint('test');
        $connection = m::mock('Illuminate\Database\Connection');

        $blueprint->softDeletesTz('deleted_at', 0, false);
        $blueprint->index(['deleted_at'], 'deleted_at_idx');
        $statements = $blueprint->toSql($connection, new \Illuminate\Database\Schema\Grammars\MySqlGrammar);

        $this->assertStringContainsString('`deleted_at` timestamp null', $statements[0]);
        $this->assertCount(2, $statements); 
    }

    public function testSoftDeletesDatetimeWithIndex()
    {
        $blueprint = new Blueprint('test');
        $connection = m::mock('Illuminate\Database\Connection');

        $blueprint->softDeletesDatetime('deleted_at', 0, true);
        $blueprint->index(['deleted_at'], 'deleted_at_idx');
        $statements = $blueprint->toSql($connection, new \Illuminate\Database\Schema\Grammars\MySqlGrammar);

        $this->assertStringContainsString('`deleted_at` datetime null', $statements[0]);
        $this->assertStringContainsString('alter table `test` add index `deleted_at_idx`(`deleted_at`)', $statements[1]);
    }

    public function testSoftDeletesDatetimeWithoutIndex()
    {
        $blueprint = new Blueprint('test');
        $connection = m::mock('Illuminate\Database\Connection');

        $blueprint->softDeletesDatetime('deleted_at', 0, false);
        $blueprint->index(['deleted_at'], 'deleted_at_idx');
        $statements = $blueprint->toSql($connection, new \Illuminate\Database\Schema\Grammars\MySqlGrammar);

        $this->assertStringContainsString('`deleted_at` datetime null', $statements[0]);
        $this->assertCount(2, $statements); 
    }
}


