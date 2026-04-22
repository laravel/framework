<?php

namespace Illuminate\Tests\JsonSchema;

use Illuminate\JsonSchema\Types\StringType;
use PHPUnit\Framework\TestCase;

class StringTypeTest extends TestCase
{
    public function test_it_sets_min_length()
    {
        $type = (new StringType)->min(5);

        $this->assertSame([
            'minLength' => 5,
            'type' => 'string',
        ], $type->toArray());
    }

    public function test_it_sets_max_length()
    {
        $type = (new StringType)->description('User handle')->max(10);

        $this->assertSame([
            'description' => 'User handle',
            'maxLength' => 10,
            'type' => 'string',
        ], $type->toArray());
    }

    public function test_it_sets_pattern()
    {
        $type = (new StringType)->default('foo')->pattern('^foo.*$');

        $this->assertSame([
            'default' => 'foo',
            'pattern' => '^foo.*$',
            'type' => 'string',
        ], $type->toArray());
    }

    public function test_it_sets_format()
    {
        $type = (new StringType)->default('foo')->format('date');

        $this->assertSame([
            'default' => 'foo',
            'format' => 'date',
            'type' => 'string',
        ], $type->toArray());
    }

    public function test_it_sets_enum()
    {
        $type = (new StringType)->enum(['draft', 'published']);

        $this->assertSame([
            'enum' => ['draft', 'published'],
            'type' => 'string',
        ], $type->toArray());
    }
}
