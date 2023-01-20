<?php

namespace Illuminate\Tests\Testing\Constraints;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Testing\Constraints\CountInDatabase;
use Mockery as m;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class CountInDatabaseTest extends TestCase
{
    protected $connection;

    protected function setUp(): void
    {
        $this->connection = m::mock(Connection::class)
            ->shouldReceive('table')
            ->once()
            ->with('mytable')
            ->andReturn(
                m::mock(Builder::class)
                    ->shouldReceive('count')
                    ->once()
                    ->andReturn(4)
                    ->getMock()
            )->getMock();
    }


    /** @test */
    public function it_can_count_items_in_the_database()
    {
        $constraint = new CountInDatabase($this->connection, 4);

        $this->assertTrue($constraint->evaluate('mytable', returnResult: true));
    }

    /** @test */
    public function it_will_fail_on_an_invalid_count()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that table [mytable] matches expected entries count of 2. Entries found: 4.' . PHP_EOL . '.');

        $constraint = new CountInDatabase($this->connection, 2);
        $constraint->evaluate('mytable' );
    }


}
