<?php

namespace Illuminate\Tests\JsonSchema;

use Illuminate\JsonSchema\Types\StringType;
use PHPUnit\Framework\TestCase;

class StringTypeTest extends TestCase
{
    public function test_it_sets_min_length()
    {
        $type = (new StringType)->min(5);

        $this->assertEquals([
            'type' => 'string',
            'minLength' => 5,
        ], $type->toArray());
    }

    public function test_it_sets_max_length()
    {
        $type = (new StringType)->description('User handle')->max(10);

        $this->assertEquals([
            'type' => 'string',
            'description' => 'User handle',
            'maxLength' => 10,
        ], $type->toArray());
    }

    public function test_it_sets_pattern()
    {
        $type = (new StringType)->default('foo')->pattern('^foo.*$');

        $this->assertEquals([
            'type' => 'string',
            'default' => 'foo',
            'pattern' => '^foo.*$',
        ], $type->toArray());
    }

    public function test_it_sets_enum()
    {
        $type = (new StringType)->enum(['draft', 'published']);

        $this->assertEquals([
            'type' => 'string',
            'enum' => ['draft', 'published'],
        ], $type->toArray());
    }
}
