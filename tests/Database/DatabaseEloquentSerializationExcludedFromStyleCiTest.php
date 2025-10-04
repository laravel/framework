<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;

class DatabaseEloquentSerializationExcludedFromStyleCiTest extends TestCase
{
    public function testModelSkipsVirtualPropertiesOnSerialization()
    {
        if (version_compare(PHP_VERSION, '8.4.0-dev', '<')) {
            $this->markTestSkipped('Requires Virtual Properties.');
        }

        $model = new EloquentModelWithVirtualPropertiesStub();
        $model->foo = 'bar';

        $serialized = serialize($model);

        $this->assertStringNotContainsString('virtualGet', $serialized);
        $this->assertStringNotContainsString('virtualSet', $serialized);

        // Ensure attributes and protected normal attributes are also serialized.
        $this->assertStringContainsString('foo', $serialized);
        $this->assertStringContainsString('bar', $serialized);
        $this->assertStringContainsString('isVisible', $serialized);
        $this->assertStringContainsString('yes', $serialized);
    }
}

if (version_compare(PHP_VERSION, '8.4.0-dev', '>=')) {
    eval(<<<'PHP'
namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Model;

class EloquentModelWithVirtualPropertiesStub extends Model
{
    public $virtualGet {
        get => $this->foo;
    }

    public $virtualSet {
        get => $this->foo;
        set {
            //
        }
    }

    protected $isVisible = 'yes';
}

PHP
    );
}
