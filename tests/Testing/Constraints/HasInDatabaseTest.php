<?php

namespace Illuminate\Tests\Testing\Constraints;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Testing\Constraints\HasInDatabase;
use Mockery as m;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class HasInDatabaseTest extends TestCase
{
    /** @test */
    public function it_can_assert_a_matching_item_in_the_database()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('table->where->count')->andReturn(1);

        $constraint = new HasInDatabase($connection, ['foo' => 'bar']);

        $this->assertTrue($constraint->evaluate('mytable', returnResult: true));
    }

    /** @test */
    public function it_will_fail_should_no_matching_entries_be_found()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage(<<<'MSG'
            Failed asserting that a row in the table [mytable] matches the attributes {
                "foo": "bar"
            }.

            attrs.
            MSG
        );


        $connection = m::mock(Connection::class);
        $connection->shouldReceive('table->where->count')->once()->andReturn(0);

        $constraint = m::mock(HasInDatabase::class, [$connection, ['foo' => 'bar']])
            ->makePartial();
        $constraint->shouldAllowMockingProtectedMethods()->shouldReceive('getAdditionalInfo')->with('mytable')->andReturn('attrs');

        $constraint->evaluate('mytable');
    }

    /** @test */
    public function it_will_retrieve_the_query_builder_to_perform_the_filter_and_count()
    {
        $builder = m::mock(Builder::class);
        $builder->shouldReceive('where')
            ->twice()
            ->with(['foo' => 'bar'])
            ->andReturnSelf();
        $builder->shouldReceive('count')
            ->twice()
            ->andReturn(1, 0);

        $connection = m::mock(Connection::class)
            ->shouldReceive('table')
            ->twice()
            ->with('mytable')
            ->andReturn($builder)
            ->getMock();

        $constraint = new HasInDatabase($connection, ['foo' => 'bar']);

        $this->assertTrue($constraint->evaluate('mytable', returnResult: true));
        $this->assertFalse($constraint->evaluate('mytable', returnResult: true));
    }
}
