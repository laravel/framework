<?php

namespace Illuminate\Tests\Testing\Constraints;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Testing\Constraints\NotSoftDeletedInDatabase;
use Mockery as m;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class NotSoftDeletedInDatabaseTest extends TestCase
{
    public function test_the_database_is_queried_for_the_correct_table_and_data()
    {
        $connection = m::mock(Connection::class)
            ->shouldReceive('table')
            ->once()
            ->with('mytable')
            ->andReturn(
                m::mock(Builder::class)
                    ->shouldReceive('where')
                    ->once()
                    ->with(['foo' => 'bar'])
                    ->andReturnSelf()
                    ->shouldReceive('whereNull')
                    ->once()
                    ->with('deleted_at')
                    ->andReturnSelf()
                    ->shouldReceive('count')
                    ->once()
                    ->andReturn(1)
                    ->getMock()
            )->getMock();

        $constraint = new NotSoftDeletedInDatabase($connection, ['foo' => 'bar'], 'deleted_at');

        $this->assertTrue($constraint->evaluate('mytable', returnResult: true));
    }

    /**
     * @test
     */
    public function it_will_fail_should_no_records_be_found()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessageMatches('/Failed\ asserting\ that\ any\ existing\ row\ in\ the\ table\ \[mytable\]\ matches\ the\ attributes\ \{\"foo\"\:\"bar\"\}\./');

        $connection = m::mock(Connection::class);
        $query = m::mock(Builder::class);
        $query->shouldReceive('where->whereNull->count')->once()->andReturn(0);
        $query->shouldReceive('limit->get')->once()->andReturn(collect());
        $connection->shouldReceive('table')->twice()->andReturn($query);

        $constraint = new NotSoftDeletedInDatabase($connection, ['foo' => 'bar'], 'deleted_at');
        $constraint->evaluate('mytable');
    }
}
