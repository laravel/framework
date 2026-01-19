<?php

namespace Illuminate\Tests\JsonSchema;

use Illuminate\JsonSchema\JsonSchema;
use PHPUnit\Framework\TestCase;

class BooleanTypeTest extends TestCase
{
    public function test_serializes_as_boolean_with_metadata(): void
    {
        $type = JsonSchema::boolean()->title('Enabled')->description('Feature flag');

        $this->assertEquals([
            'type' => 'boolean',
            'title' => 'Enabled',
            'description' => 'Feature flag',
        ], $type->toArray());
    }

    public function test_may_set_default_true_via_default(): void
    {
        $type = JsonSchema::boolean()->default(true);

        $this->assertEquals([
            'type' => 'boolean',
            'default' => true,
        ], $type->toArray());
    }

    public function test_may_set_default_false_via_default(): void
    {
        $type = JsonSchema::boolean()->default(false);

        $this->assertEquals([
            'type' => 'boolean',
            'default' => false,
        ], $type->toArray());
    }

    public function test_may_set_enum(): void
    {
        $type = JsonSchema::boolean()->enum([true, false]);

        $this->assertEquals([
            'type' => 'boolean',
            'enum' => [true, false],
        ], $type->toArray());
    }
}
