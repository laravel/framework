<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Concerns\BuildsQueries;

class DatabaseConcernsBuildsQueriesTraitTest extends TestCase
{
    public function testTapCallbackInstance()
    {
        $mock = $this->getMockForTrait(BuildsQueries::class);
        $mock->tap(function ($builder) use ($mock) {
            $this->assertEquals($mock, $builder);
        });
    }
}
